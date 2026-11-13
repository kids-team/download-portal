import { useCallback, useEffect, useRef, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api, useApiQuery } from "@/lib/api";
import { DocumentContent } from "@/features/documents/DocumentContent";
import { DocumentInspector } from "@/features/documents/DocumentInspector";
import { DocumentTree } from "@/features/documents/DocumentTree";
import { useTranslation } from "@/i18n/I18nProvider";
import type { DocumentEditor } from "@/features/documents/BlockNoteEditor";
import { BibleReferenceSelectionContext, type SelectedBibleReference } from "@/features/documents/BibleReferenceSelection";
import { BibleReferenceDialog } from "@/features/documents/BibleReferenceDialog";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

function filterTree(nodes: any[], searchTerm: string, untitledLabel: string, locale: string): any[] {
    return nodes.reduce<any[]>((filteredNodes, node: any) => {
        const children = filterTree(node.children ?? [], searchTerm, untitledLabel, locale);
        const title = !node.title?.trim() || node.title === untitledLabel
            ? untitledLabel
            : node.title;
        const matches = title.toLocaleLowerCase(locale).includes(searchTerm);

        if (matches || children.length > 0) {
            filteredNodes.push({ ...node, children });
        }

        return filteredNodes;
    }, []);
}

function collectionItems(data: any): any[] {
    if (Array.isArray(data)) {
        return data;
    }

    return Array.isArray(data?.data) ? data.data : [];
}

export function DocumentsWorkspace({ user }: any) {
    const { locale, t } = useTranslation();
    const cache = useQueryClient();
    const [selectedId, setSelectedId] = useState(null);
    const [search, setSearch] = useState("");
    const [actionError, setActionError] = useState("");
    const [draft, setDraft] = useState<any>(null);
    const [editor, setEditor] = useState<DocumentEditor | null>(null);
    const [selectedBoxId, setSelectedBoxId] = useState<string | null>(null);
    const [treeCollapsed, setTreeCollapsed] = useState(false);
    const [selectedReference, setSelectedReference] = useState<SelectedBibleReference | null>(null);
    const [renamingDocument, setRenamingDocument] = useState<{ id: number; title: string } | null>(null);
    const [renamedTitle, setRenamedTitle] = useState("");
    const [deletingDocument, setDeletingDocument] = useState<{ id: number; title: string } | null>(null);
    const [inspectorContext, setInspectorContext] = useState<"document" | "block">("document");
    const lastBoxId = useRef<string | null>(null);
    const onBoxSelection = useCallback((boxId: string | null) => {
        setSelectedBoxId(boxId);
        if (!boxId) {
            setInspectorContext("document");
        } else if (boxId !== lastBoxId.current) {
            setInspectorContext("block");
            lastBoxId.current = boxId;
        }
    }, []);
    const selectReference = useCallback((reference: SelectedBibleReference) => {
        setSelectedReference(reference);
    }, []);
    const clearReference = useCallback((id: symbol) => {
        setSelectedReference((current) => current?.id === id ? null : current);
    }, []);
    useEffect(() => {
        if (!selectedBoxId) {
            setInspectorContext("document");
        }
    }, [selectedBoxId]);
    const tree = useApiQuery("/api/v1/admin/documents/tree");
    const document = useApiQuery(`/api/v1/admin/documents/${selectedId}`, { enabled: selectedId !== null });
    const categories = useApiQuery("/api/v1/admin/categories");
    const audiences = useApiQuery("/api/v1/admin/audiences");
    const tags = useApiQuery("/api/v1/admin/tags");

    useEffect(() => {
        if (document.data) {
            setDraft(document.data);
        }
    }, [document.data]);

    const save = useMutation<any, any, any>({
        mutationFn: (value) => {
            const { is_locked, published_at, status, ...attributes } = value;

            return api.patch(
                `/api/v1/admin/documents/${value.id}`,
                user.role === "admin" ? value : attributes,
            );
        },
        onSuccess: () => {
            cache.invalidateQueries({ queryKey: [`/api/v1/admin/documents/${selectedId}`] });
            cache.invalidateQueries({ queryKey: ["/api/v1/admin/documents/tree"] });
        },
    });
    const remove = useMutation<any, any, any>({
        mutationFn: (documentId) => api.delete(`/api/v1/admin/documents/${documentId}`),
        onMutate: () => setActionError(""),
        onSuccess: (_response, documentId) => {
            cache.invalidateQueries({ queryKey: ["/api/v1/admin/documents/tree"] });
            cache.removeQueries({ queryKey: [`/api/v1/admin/documents/${documentId}`] });
            if (documentId === selectedId) {
                setSelectedId(null);
                setDraft(null);
            }
        },
        onError: (error) => {
            setActionError(error.response?.data?.message ?? t("documents.deleteError"));
        },
    });
    const updateTreeDocument = useMutation<any, any, { id: number; changes: Record<string, unknown> }>({
        mutationFn: ({ id, changes }) => api.patch(`/api/v1/admin/documents/${id}`, changes),
        onMutate: () => setActionError(""),
        onSuccess: (_response, { id, changes }) => {
            cache.invalidateQueries({ queryKey: ["/api/v1/admin/documents/tree"] });
            if (id === selectedId) {
                setDraft((current: any) => current?.id === id ? { ...current, ...changes } : current);
            } else {
                cache.invalidateQueries({ queryKey: [`/api/v1/admin/documents/${id}`] });
            }
        },
        onError: (error) => setActionError(error.response?.data?.message ?? t("documents.updateError")),
    });
    const create = useMutation<any, any, any>({
        mutationFn: (parentId = null) => {
            return api.post("/api/v1/admin/documents", {
                parent_id: parentId,
                title: t("documents.untitled"),
                slug: `dokument-${Date.now()}`,
                status: "draft",
            });
        },
        onMutate: () => setActionError(""),
        onSuccess: (response) => {
            cache.invalidateQueries({ queryKey: ["/api/v1/admin/documents/tree"] });
            setSelectedId(response.data.data.id);
        },
        onError: (error) => {
            setActionError(error.response?.data?.message ?? t("documents.createError"));
        },
    });
    const move = useMutation<any, any, any>({
        mutationFn: ({ documentId, parentId, position }) => api.patch(
            `/api/v1/admin/documents/${documentId}/tree-position`,
            { parent_id: parentId, position },
        ),
        onMutate: () => setActionError(""),
        onSuccess: () => {
            cache.invalidateQueries({ queryKey: ["/api/v1/admin/documents/tree"] });
        },
        onError: (error) => {
            setActionError(error.response?.data?.message ?? t("documents.moveError"));
        },
    });

    const normalizedSearch = search.trim().toLocaleLowerCase(locale);
    const nodes = normalizedSearch === "" ? (tree.data ?? []) : filterTree(tree.data ?? [], normalizedSearch, t("documents.untitled"), locale);
    const selectDocument = (documentId: any) => {
        lastBoxId.current = null;
        setSelectedBoxId(null);
        setSelectedReference(null);
        setInspectorContext("document");
        setSelectedId(documentId);
        setDraft(null);
    };

    return (
        <BibleReferenceSelectionContext.Provider value={{ select: selectReference, clear: clearReference }}>
        <div className={`grid min-h-[calc(100svh-57px)] grid-cols-1 ${treeCollapsed ? "lg:grid-cols-[3.5rem_minmax(0,1fr)_22rem]" : "lg:grid-cols-[18rem_minmax(0,1fr)_22rem]"}`}>
            <DocumentTree
                nodes={nodes}
                collapsed={treeCollapsed}
                onToggleCollapsed={() => setTreeCollapsed((current) => !current)}
                selectedId={selectedId}
                onSelect={selectDocument}
                search={search}
                onSearchChange={setSearch}
                onCreate={() => create.mutate()}
                onCreateChild={(parentId: any) => create.mutate(parentId)}
                onRename={(node: any) => {
                    setActionError("");
                    setRenamingDocument(node);
                    setRenamedTitle(node.title ?? "");
                }}
                onDelete={(node: any) => {
                    setActionError("");
                    setDeletingDocument(node);
                }}
                onChangeStatus={(node: any) => {
                    const published = node.status !== "published";
                    if (published && !window.confirm(t("documents.publishSavedConfirmation"))) {
                        return;
                    }
                    updateTreeDocument.mutate({ id: node.id, changes: { status: published ? "published" : "draft", published_at: published ? new Date().toISOString() : null } });
                }}
                canManagePublication={user.role === "admin"}
                onMove={(documentId: any, parentId: any, position: any) => move.mutate({ documentId, parentId, position })}
                creating={create.isPending}
                moving={move.isPending}
                error={actionError}
            />
            <DocumentContent selectedId={selectedId} document={document} draft={draft} onDraftChange={setDraft} onEditorChange={setEditor} onBoxSelection={onBoxSelection} />
            {draft && <DocumentInspector draft={draft} categories={collectionItems(categories.data)} audiences={collectionItems(audiences.data)} tags={collectionItems(tags.data)} onDraftChange={setDraft} onSave={() => save.mutate(draft)} onDelete={() => { setActionError(""); setDeletingDocument({ id: draft.id, title: draft.title }); }} canDelete={user.role === "admin"} canManagePublication={user.role === "admin"} onBibleReferencesChange={() => cache.invalidateQueries({ queryKey: [`/api/v1/admin/documents/${selectedId}`] })} saving={save.isPending} deleting={remove.isPending} editor={editor} selectedBoxId={selectedBoxId} inspectorContext={inspectorContext} onInspectorContextChange={setInspectorContext} />}
        </div>
        <Dialog open={renamingDocument !== null} onOpenChange={(open) => { if (!open && !updateTreeDocument.isPending) { setRenamingDocument(null); setActionError(""); } }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t("documents.rename")}</DialogTitle>
                    <DialogDescription>{t("documents.renameDescription")}</DialogDescription>
                </DialogHeader>
                <form className="grid gap-4" onSubmit={(event) => {
                    event.preventDefault();
                    const title = renamedTitle.trim();
                    if (!renamingDocument || !title) { return; }
                    if (title === renamingDocument.title) { setRenamingDocument(null); return; }
                    updateTreeDocument.mutate({ id: renamingDocument.id, changes: { title } }, { onSuccess: () => setRenamingDocument(null) });
                }}>
                    <div className="grid gap-2">
                        <Label htmlFor="rename-document-title">{t("documents.renamePrompt")}</Label>
                        <Input id="rename-document-title" value={renamedTitle} onChange={(event) => setRenamedTitle(event.target.value)} maxLength={255} autoFocus required />
                    </div>
                    {actionError && <p className="text-sm text-destructive" role="alert">{actionError}</p>}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => { setRenamingDocument(null); setActionError(""); }} disabled={updateTreeDocument.isPending}>{t("actions.cancel")}</Button>
                        <Button type="submit" disabled={!renamedTitle.trim() || updateTreeDocument.isPending}>{t("actions.save")}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <Dialog open={deletingDocument !== null} onOpenChange={(open) => { if (!open && !remove.isPending) { setDeletingDocument(null); setActionError(""); } }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t("documents.delete")}</DialogTitle>
                    <DialogDescription>{t("documents.deleteNamedConfirmation", { title: deletingDocument?.title ?? "" })}</DialogDescription>
                </DialogHeader>
                {actionError && <p className="text-sm text-destructive" role="alert">{actionError}</p>}
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => { setDeletingDocument(null); setActionError(""); }} disabled={remove.isPending}>{t("actions.cancel")}</Button>
                    <Button type="button" variant="destructive" disabled={remove.isPending} onClick={() => { if (deletingDocument) { remove.mutate(deletingDocument.id, { onSuccess: () => setDeletingDocument(null) }); } }}>{t("documents.delete")}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        {selectedReference && <BibleReferenceDialog reference={selectedReference.reference} onClose={() => setSelectedReference(null)} onConfirm={(reference) => {
            selectedReference.update(reference);
            setSelectedReference(null);
        }} />}
        </BibleReferenceSelectionContext.Provider>
    );
}
