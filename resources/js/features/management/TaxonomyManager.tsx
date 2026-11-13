import { useEffect, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Pencil, Plus, Trash2, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { api, useApiQuery } from "@/lib/api";
import { slugify } from "@/lib/slugify";
import { useTranslation } from "@/i18n/I18nProvider";

const emptyItem = { name: "", slug: "" };

export function TaxonomyManager({ endpoint, translationKey }: any) {
    const { t, locale } = useTranslation();
    const title = t(`${translationKey}.title`);
    const singular = t(`${translationKey}.singular`);
    const description = t(`${translationKey}.description`);
    const queryPath = `/api/v1/admin/${endpoint}`;
    const cache = useQueryClient();
    const listing = useApiQuery(queryPath);
    const [form, setForm] = useState(emptyItem);
    const [editingId, setEditingId] = useState(null);
    const items = listing.data?.data ?? [];
    const refresh = () => cache.invalidateQueries({ queryKey: [queryPath] });
    const save = useMutation({
        mutationFn: () => editingId
            ? api.patch(`${queryPath}/${editingId}`, form)
            : api.post(queryPath, form),
        onSuccess: () => {
            setForm(emptyItem);
            setEditingId(null);
            refresh();
        },
    });
    const remove = useMutation({
        mutationFn: (id) => api.delete(`${queryPath}/${id}`),
        onSuccess: refresh,
    });

    useEffect(() => {
        if (listing.isError) {
            setForm(emptyItem);
        }
    }, [listing.isError]);

    const changeName = (name: string) => setForm((current) => ({ ...current, name, slug: slugify(name) }));
    const startEdit = (item: any) => {
        setEditingId(item.id);
        setForm({ name: item.name, slug: item.slug });
    };
    const cancelEdit = () => {
        setEditingId(null);
        setForm(emptyItem);
    };

    return (
        <section className="mx-auto grid w-full max-w-3xl gap-6">
            <header>
                <h1 className="text-2xl font-semibold">{title}</h1>
                <p className="mt-1 text-sm text-slate-500">{description}</p>
            </header>
            <form className="grid gap-4 rounded-lg border bg-white p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end" onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
                <div className="grid gap-2">
                    <Label htmlFor={`${endpoint}-name`}>{t("taxonomy.name")}</Label>
                    <Input id={`${endpoint}-name`} value={form.name} onChange={(event) => changeName(event.target.value)} required />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={`${endpoint}-slug`}>{t("taxonomy.slug")}</Label>
                    <Input id={`${endpoint}-slug`} value={form.slug} onChange={(event) => setForm((current) => ({ ...current, slug: slugify(event.target.value) }))} required />
                </div>
                <div className="flex gap-2">
                    <Button type="submit" disabled={save.isPending || !form.name || !form.slug}><Plus /> {editingId ? t("actions.save") : t("taxonomy.create", { singular })}</Button>
                    {editingId && <Button type="button" variant="outline" size="icon" onClick={cancelEdit} aria-label={t("taxonomy.cancelEdit")}><X /></Button>}
                </div>
                {save.isError && <p className="text-sm text-red-600 sm:col-span-3">{t("taxonomy.saveError")}</p>}
            </form>
            <div className="overflow-hidden rounded-lg border bg-white">
                {listing.isLoading && <p className="p-4 text-sm text-slate-500">{t("taxonomy.loading")}</p>}
                {listing.isError && <p className="p-4 text-sm text-red-600">{t("taxonomy.loadError")}</p>}
                {!listing.isLoading && !listing.isError && items.length === 0 && <p className="p-4 text-sm text-slate-500">{t("taxonomy.empty", { title: title.toLocaleLowerCase(locale) })}</p>}
                {items.map((item: any) => (
                    <div key={item.id} className="flex items-center gap-3 border-b px-4 py-3 last:border-b-0">
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{item.name}</p>
                            <p className="truncate text-xs text-slate-500">{item.slug}</p>
                        </div>
                        <Button type="button" variant="ghost" size="icon" onClick={() => startEdit(item)} aria-label={t("taxonomy.edit", { name: item.name })}><Pencil /></Button>
                        <Button type="button" variant="ghost" size="icon" className="text-red-600 hover:text-red-700" onClick={() => remove.mutate(item.id)} disabled={remove.isPending} aria-label={t("taxonomy.remove", { name: item.name })}><Trash2 /></Button>
                    </div>
                ))}
            </div>
        </section>
    );
}
