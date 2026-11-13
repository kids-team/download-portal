import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { closestCenter, DndContext, KeyboardSensor, PointerSensor, useSensor, useSensors, type DragEndEvent } from "@dnd-kit/core";
import { arrayMove, rectSortingStrategy, SortableContext, sortableKeyboardCoordinates, useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { GripVertical } from "lucide-react";
import { useTranslation } from "@/i18n/I18nProvider";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { api, useApiQuery } from "@/lib/api";

const widgetIds = ["draft", "published", "media"] as const;
type WidgetId = typeof widgetIds[number];
type DashboardData = { documents_by_status: Record<string, number>; media_assets: number };

function normalizeOrder(saved: string[] | null): WidgetId[] {
    const known = (saved ?? []).filter((id): id is WidgetId => widgetIds.some((widgetId) => widgetId === id));
    return [...new Set(known), ...widgetIds.filter((id) => !known.includes(id))];
}

function SortableWidget({ id, label, value, disabled }: { id: WidgetId; label: string; value: number | string; disabled: boolean }) {
    const { t } = useTranslation();
    const { attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition, isDragging } = useSortable({ id, disabled });

    return (
        <Card ref={setNodeRef} style={{ transform: CSS.Transform.toString(transform), transition }} className={isDragging ? "relative z-10 opacity-70" : ""}>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle className="text-sm text-slate-500">{label}</CardTitle>
                <button ref={setActivatorNodeRef} type="button" className="touch-none cursor-grab rounded p-1 text-slate-500 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-blue-600 active:cursor-grabbing disabled:cursor-wait disabled:opacity-50" aria-label={t("dashboard.moveWidget", { title: label })} {...attributes} {...listeners} disabled={disabled}>
                    <GripVertical className="size-4" aria-hidden="true" />
                </button>
            </CardHeader>
            <CardContent className="text-3xl font-semibold">{value}</CardContent>
        </Card>
    );
}

function WidgetGrid({ initialOrder, dashboard }: { initialOrder: string[] | null; dashboard: DashboardData | undefined }) {
    const { t } = useTranslation();
    const cache = useQueryClient();
    const [order, setOrder] = useState(() => normalizeOrder(initialOrder));
    const [error, setError] = useState(false);
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );
    const save = useMutation({
        mutationFn: (widgets: WidgetId[]) => api.put("/api/v1/admin/dashboard/layout", { widgets }),
        onSuccess: (_response, widgets) => cache.setQueryData(["/api/v1/admin/dashboard/layout"], { widgets }),
    });
    const labels: Record<WidgetId, string> = {
        draft: t("documents.draft"),
        published: t("documents.published"),
        media: t("media.title"),
    };
    const values: Record<WidgetId, number | string> = {
        draft: dashboard ? dashboard.documents_by_status.draft ?? 0 : "–",
        published: dashboard ? dashboard.documents_by_status.published ?? 0 : "–",
        media: dashboard?.media_assets ?? "–",
    };
    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id || save.isPending) return;
        const oldIndex = order.indexOf(active.id as WidgetId);
        const newIndex = order.indexOf(over.id as WidgetId);
        if (oldIndex < 0 || newIndex < 0) return;

        const previous = order;
        const next = arrayMove(order, oldIndex, newIndex);
        setError(false);
        setOrder(next);
        save.mutate(next, { onError: () => { setOrder(previous); setError(true); } });
    };

    return (
        <>
            {error && <p role="alert" className="mb-4 text-sm text-red-600">{t("dashboard.saveError")}</p>}
            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd} accessibility={{
                screenReaderInstructions: { draggable: t("dashboard.dragInstructions") },
                announcements: {
                    onDragStart: ({ active }) => t("dashboard.dragStart", { title: labels[active.id as WidgetId] }),
                    onDragOver: ({ active, over }) => over ? t("dashboard.dragOver", { title: labels[active.id as WidgetId], target: labels[over.id as WidgetId] }) : t("dashboard.dragOutside"),
                    onDragEnd: ({ active, over }) => over ? t("dashboard.dragEnd", { title: labels[active.id as WidgetId], target: labels[over.id as WidgetId] }) : t("dashboard.dragCancel"),
                    onDragCancel: () => t("dashboard.dragCancel"),
                },
            }}>
                <SortableContext items={order} strategy={rectSortingStrategy}>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {order.map((id) => <SortableWidget key={id} id={id} label={labels[id]} value={values[id]} disabled={save.isPending} />)}
                    </div>
                </SortableContext>
            </DndContext>
        </>
    );
}

export function Dashboard() {
    const { t } = useTranslation();
    const dashboard = useApiQuery<DashboardData>("/api/v1/admin/dashboard");
    const layout = useApiQuery<{ widgets: string[] | null }>("/api/v1/admin/dashboard/layout");

    return (
        <main className="p-6">
            <h1 className="mb-6 text-2xl font-semibold">{t("navigation.dashboard")}</h1>
            {layout.isLoading ? <p className="text-sm text-slate-500">{t("dashboard.loading")}</p>
                : layout.isError ? <p role="alert" className="text-sm text-red-600">{t("dashboard.loadError")}</p>
                : <WidgetGrid initialOrder={layout.data?.widgets ?? null} dashboard={dashboard.data} />}
            {dashboard.isError && <p role="alert" className="mt-4 text-sm text-red-600">{t("dashboard.dataError")}</p>}
        </main>
    );
}
