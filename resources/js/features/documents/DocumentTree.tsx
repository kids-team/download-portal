import { useMemo, useState } from "react";
import { ContextMenu } from "@base-ui/react/context-menu";
import {
    closestCenter,
    DndContext,
    DragOverlay,
    PointerSensor,
    useDroppable,
    useSensor,
    useSensors,
} from "@dnd-kit/core";
import {
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { ChevronRight, FilePenLine, FilePlus, FileText, Folder, FolderPen, Globe, PanelLeftClose, PanelLeftOpen, Pencil, RotateCcw, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useTranslation } from "@/i18n/I18nProvider";

function isUntitledDocument(title: string | null | undefined, untitledLabel: string) {
    return !title?.trim() || title === untitledLabel;
}

function HighlightedTitle({ title, searchTerm }: any) {
    if (searchTerm === "") {
        return title;
    }

    const parts = title.split(new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`, "gi"));

    return parts.map((part: string, index: number) => (
        part.toLocaleLowerCase("de") === searchTerm.toLocaleLowerCase("de")
            ? <mark key={index} className="rounded bg-amber-200 px-0.5 text-inherit">{part}</mark>
            : part
    ));
}

function ArticleDropTarget({ node, selectedId, onSelect, searchTerm, untitledLabel }: any) {
    const { t } = useTranslation();
    const isUntitled = isUntitledDocument(node.title, untitledLabel);
    const title = isUntitled ? untitledLabel : node.title;
    const { setNodeRef, isOver } = useDroppable({
        id: `article-${node.id}`,
        data: {
            dropType: "article",
            parentId: node.id,
            position: node.children?.length ?? 0,
        },
    });

    return (
        <button
            type="button"
            ref={setNodeRef}
            className={`min-w-0 flex-1 truncate rounded px-1 text-left ${isOver ? "bg-blue-200 text-blue-800" : node.status === "draft" || isUntitled ? "text-slate-500" : ""} ${selectedId === node.id ? "font-medium" : ""}`}
            onClick={() => onSelect(node.id)}
            title={t("documents.dropAsChild")}
        >
            <HighlightedTitle title={title} searchTerm={searchTerm} />
            {node.status === "draft" && <span className="sr-only"> ({t("documents.draft")})</span>}
        </button>
    );
}

function RootDropTarget({ position }: any) {
    const { t } = useTranslation();
    const { setNodeRef, isOver } = useDroppable({
        id: "root-drop-target",
        data: {
            dropType: "root",
            parentId: null,
            position,
        },
    });

    return (
        <div
            ref={setNodeRef}
            className={`mt-3 rounded-md border border-dashed px-2 py-2 text-center text-xs transition-colors ${isOver ? "border-blue-500 bg-blue-100 text-blue-800" : "border-slate-300 text-slate-500"}`}
        >
            {t("documents.dropRoot")}
        </div>
    );
}

function TreeNode({ node, selectedId, onSelect, onCreateChild, onRename, onDelete, onChangeStatus, canManagePublication, creating, forceOpen, expandedIds, setExpanded, searchTerm, untitledLabel }: any) {
    const { t } = useTranslation();
    const children = node.children ?? [];
    const isOpen = forceOpen || expandedIds.has(node.id);
    const title = isUntitledDocument(node.title, untitledLabel) ? untitledLabel : node.title;
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: node.id,
    });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };
    const itemClass = "flex cursor-default items-center gap-2 rounded px-3 py-2 text-sm outline-none data-highlighted:bg-slate-100 data-disabled:opacity-40";
    const menuItems = (Item: typeof ContextMenu.Item) => <>
        <Item className={itemClass} disabled={node.is_locked && !canManagePublication} onClick={() => onRename(node)}><Pencil className="size-4 shrink-0" aria-hidden="true" />{t("documents.rename")}</Item>
        {canManagePublication && <Item className={itemClass} onClick={() => onChangeStatus(node)}>{node.status === "published" ? <RotateCcw className="size-4 shrink-0" aria-hidden="true" /> : <Globe className="size-4 shrink-0" aria-hidden="true" />}{t(node.status === "published" ? "documents.returnToDraft" : "documents.publish")}</Item>}
        <Item className={itemClass} disabled={creating} onClick={() => { setExpanded(node.id, true); onCreateChild(node.id); }}><FilePlus className="size-4 shrink-0" aria-hidden="true" />{t("documents.createChild")}</Item>
        {canManagePublication && <Item className={`${itemClass} text-destructive`} disabled={children.length > 0} onClick={() => onDelete(node)}><Trash2 className="size-4 shrink-0" aria-hidden="true" />{t("documents.delete")}</Item>}
    </>;

    return (
        <li ref={setNodeRef} style={style} className={isDragging ? "opacity-30" : ""}>
            <ContextMenu.Root>
            <ContextMenu.Trigger className={`flex items-center gap-1 rounded-md px-1 py-1 text-sm hover:bg-slate-100 ${selectedId === node.id ? "bg-slate-200 font-medium" : ""}`}>
                <button
                    type="button"
                    className="grid size-6 place-items-center"
                    onClick={() => setExpanded(node.id, !isOpen)}
                    aria-label={t(isOpen ? "documents.collapse" : "documents.expand", { title })}
                >
                    {children.length > 0 && <ChevronRight className={`size-4 transition-transform ${isOpen ? "rotate-90" : ""}`} />}
                </button>
                <button
                    type="button"
                    className="grid size-6 cursor-grab place-items-center rounded active:cursor-grabbing"
                    aria-label={t("documents.move", { title })}
                    {...attributes}
                    {...listeners}
                >
                    {children.length > 0 ? (node.status === "draft" ? <FolderPen className="size-4 text-slate-400" /> : <Folder className="size-4 text-slate-500" />) : node.status === "draft" ? <FilePenLine className="size-4 text-slate-400" /> : <FileText className="size-4 text-slate-500" />}
                </button>
                <ArticleDropTarget node={node} selectedId={selectedId} onSelect={onSelect} searchTerm={searchTerm} untitledLabel={untitledLabel} />
            </ContextMenu.Trigger>
            <ContextMenu.Portal><ContextMenu.Positioner className="z-50"><ContextMenu.Popup className="grid min-w-48 rounded-md border bg-white p-1 shadow-lg outline-none">{menuItems(ContextMenu.Item)}</ContextMenu.Popup></ContextMenu.Positioner></ContextMenu.Portal>
            </ContextMenu.Root>
            {isOpen && children.length > 0 && (
                <SortableTree nodes={children} selectedId={selectedId} onSelect={onSelect} onCreateChild={onCreateChild} onRename={onRename} onDelete={onDelete} onChangeStatus={onChangeStatus} canManagePublication={canManagePublication} creating={creating} forceOpen={forceOpen} expandedIds={expandedIds} setExpanded={setExpanded} searchTerm={searchTerm} parentId={node.id} untitledLabel={untitledLabel} />
            )}
        </li>
    );
}

function SortableTree({ nodes, selectedId, onSelect, onCreateChild, onRename, onDelete, onChangeStatus, canManagePublication, creating, forceOpen, expandedIds, setExpanded, searchTerm, parentId = null, untitledLabel }: any) {
    const containerId = parentId === null ? "root" : `children-${parentId}`;

    return (
        <SortableContext id={containerId} items={nodes.map((node: any) => node.id)} strategy={verticalListSortingStrategy}>
            <ul className={parentId === null ? "" : "ml-4 border-l pl-2"}>
                {nodes.map((node: any) => <TreeNode key={node.id} node={node} selectedId={selectedId} onSelect={onSelect} onCreateChild={onCreateChild} onRename={onRename} onDelete={onDelete} onChangeStatus={onChangeStatus} canManagePublication={canManagePublication} creating={creating} forceOpen={forceOpen} expandedIds={expandedIds} setExpanded={setExpanded} searchTerm={searchTerm} untitledLabel={untitledLabel} />)}
            </ul>
        </SortableContext>
    );
}

function findNode(nodes: any[], nodeId: any): any {
    for (const node of nodes) {
        if (node.id === nodeId) {
            return node;
        }

        const child = findNode(node.children ?? [], nodeId);

        if (child) {
            return child;
        }
    }

    return null;
}

export function DocumentTree({ nodes, selectedId, onSelect, search, onSearchChange, onCreate, onCreateChild, onRename, onDelete, onChangeStatus, canManagePublication, onMove, creating, moving, error, collapsed, onToggleCollapsed }: any) {
    const { t } = useTranslation();
    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }));
    const [activeId, setActiveId] = useState<any>(null);
    const [expandedIds, setExpandedIds] = useState<Set<number>>(() => new Set());
    const setExpanded = (id: number, open: boolean) => setExpandedIds((current) => {
        const next = new Set(current);
        if (open) { next.add(id); } else { next.delete(id); }
        return next;
    });
    const activeNode = useMemo(() => activeId === null ? null : findNode(nodes, activeId), [activeId, nodes]);

    const handleDragEnd = ({ active, over }: any) => {
        setActiveId(null);

        if (!over || active.id === over.id) {
            return;
        }

        const target = over.data.current;

        if (target?.dropType === "root") {
            onMove(active.id, null, target.position);

            return;
        }

        if (target?.dropType === "article") {
            if (active.id === target.parentId) {
                return;
            }

            onMove(active.id, target.parentId, target.position);

            return;
        }

        const sortable = target?.sortable;

        if (!sortable) {
            return;
        }

        const parentId = sortable.containerId === "root"
            ? null
            : Number(sortable.containerId.replace("children-", ""));

        onMove(active.id, parentId, sortable.index);
    };

    return (
        <aside className="border-r bg-white p-3">
            <div className="mb-3 flex items-center justify-between gap-2">
                <Button variant="outline" size="icon" onClick={onToggleCollapsed} aria-label={t(collapsed ? "documents.openDrawer" : "documents.closeDrawer")} title={t(collapsed ? "documents.openDrawer" : "documents.closeDrawer")} aria-expanded={!collapsed} aria-controls="document-tree-content">
                    {collapsed ? <PanelLeftOpen aria-hidden="true" /> : <PanelLeftClose aria-hidden="true" />}
                </Button>
                {!collapsed && <Button variant="outline" size="icon" onClick={onCreate} disabled={creating} aria-label={t("documents.create")} title={t("documents.create")}><FilePlus aria-hidden="true" /></Button>}
            </div>
            <div id="document-tree-content" hidden={collapsed}>
            <div className="mb-3 flex gap-2">
                <Input placeholder={t("documents.search")} value={search} onChange={(event) => onSearchChange(event.target.value)} />
            </div>
            {error && <p className="mb-3 text-sm text-red-600">{error}</p>}
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragStart={({ active }) => setActiveId(active.id)}
                onDragCancel={() => setActiveId(null)}
                onDragEnd={handleDragEnd}
            >
                <div className={moving ? "pointer-events-none opacity-60" : ""}>
                    <SortableTree nodes={nodes} selectedId={selectedId} onSelect={onSelect} onCreateChild={onCreateChild} onRename={onRename} onDelete={onDelete} onChangeStatus={onChangeStatus} canManagePublication={canManagePublication} creating={creating} forceOpen={search.trim() !== ""} expandedIds={expandedIds} setExpanded={setExpanded} searchTerm={search.trim()} untitledLabel={t("documents.untitled")} />
                    <RootDropTarget position={nodes.length} />
                </div>
                <DragOverlay>
                    {activeNode && <div className="rounded-md bg-slate-800 px-2 py-1 text-sm text-white shadow-lg">{isUntitledDocument(activeNode.title, t("documents.untitled")) ? t("documents.untitled") : activeNode.title}</div>}
                </DragOverlay>
            </DndContext>
            </div>
        </aside>
    );
}
