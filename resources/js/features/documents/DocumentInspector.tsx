import { useRef, useState } from "react";
import { Tabs } from "@base-ui/react/tabs";
import { useCreateBlockNote } from "@blocknote/react";
import { BlockNoteView } from "@blocknote/shadcn";
import {
    FileText,
    FileArchive,
    FileAudio,
    FileImage,
    FileSpreadsheet,
    FileVideo,
    FileWarning,
    Eye,
    Paperclip,
    Settings,
    Tags,
    Trash2,
    Upload,
} from "lucide-react";
import { InspectorPanel } from "@/components/inspector-panel";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { useTranslation } from "@/i18n/I18nProvider";
import { slugify } from "@/lib/slugify";
import { CoverImagePanel } from "@/features/documents/CoverImagePanel";
import { BibleReferencesPanel } from "@/features/documents/BibleReferencesPanel";
import { BoxInspector } from "@/features/documents/BoxInspector";
import { ImageInspector } from "@/features/documents/ImageInspector";
import { BibleReferenceSelectionContext } from "@/features/documents/BibleReferenceSelection";
import { documentSchema } from "@/features/documents/documentSchema";

function EmptyIntegrationHint({ children }: any) {
    return <p className="text-sm text-slate-500">{children}</p>;
}

function hasContent(node: any): boolean {
    if (typeof node === "string") {
        return node.trim() !== "";
    }

    if (Array.isArray(node)) {
        return node.some(hasContent);
    }

    if (node === null || typeof node !== "object") {
        return false;
    }

    if (typeof node.text === "string" && node.text.trim() !== "") {
        return true;
    }

    return hasContent(node.content) || hasContent(node.children);
}

function dateTimeLocalValue(value: any): string {
    if (!value) {
        return "";
    }

    const date = new Date(value);
    const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60_000));

    return localDate.toISOString().slice(0, 16);
}

function DocumentPublicationPanel({ draft, onChange, canManagePublication }: any) {
    const { t } = useTranslation();
    return (
        <InspectorPanel title={t("documents.panel")} icon={Settings} defaultOpen>
            <div className="grid gap-2">
                <Label htmlFor="document-slug">{t("documents.slug")}</Label>
                <Input
                    id="document-slug"
                    value={draft.slug ?? ""}
                    onChange={(event) => onChange({ slug: slugify(event.target.value) })}
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="document-status">{t("documents.status")}</Label>
                <select
                    id="document-status"
                    className="h-9 rounded-md border px-3 text-sm"
                    value={draft.status}
                    onChange={(event) => onChange({ status: event.target.value })}
                    disabled={!canManagePublication}
                >
                    <option value="draft">{t("documents.draft")}</option>
                    <option value="published">{t("documents.published")}</option>
                </select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="document-published-at">{t("documents.publicationDate")}</Label>
                <Input
                    id="document-published-at"
                    type="datetime-local"
                    value={dateTimeLocalValue(draft.published_at)}
                    onChange={(event) => onChange({ published_at: event.target.value ? new Date(event.target.value).toISOString() : null })}
                    disabled={!canManagePublication}
                />
            </div>
        </InspectorPanel>
    );
}

function SummaryPanel({ summary, onChange }: any) {
    const { t } = useTranslation();
    return (
        <InspectorPanel title={t("documents.summary")} icon={FileText}>
            <div className="grid gap-2">
                <Label htmlFor="document-summary">{t("documents.summary")}</Label>
                <Textarea
                    id="document-summary"
                    value={summary ?? ""}
                    onChange={(event) => onChange({ summary: event.target.value })}
                />
                <p className="text-xs text-slate-500">
                    {t("documents.summaryHint")}
                </p>
            </div>
        </InspectorPanel>
    );
}

function TaxonomyPanel({ draft, categories, audiences, onChange }: any) {
    const { t } = useTranslation();
    return (
        <InspectorPanel title={t("documents.taxonomy")} icon={Tags}>
            <div className="grid gap-2">
                <Label htmlFor="document-category">{t("documents.category")}</Label>
                <select
                    id="document-category"
                    className="h-9 rounded-md border px-3 text-sm"
                    value={draft.category_id ?? ""}
                    onChange={(event) => onChange({ category_id: event.target.value || null })}
                >
                    <option value="">{t("documents.noCategory")}</option>
                    {categories.map((category: any) => (
                        <option key={category.id} value={category.id}>{category.name}</option>
                    ))}
                </select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="document-label">{t("documents.label")}</Label>
                <Input
                    id="document-label"
                    value={draft.label ?? ""}
                    onChange={(event) => onChange({ label: event.target.value })}
                />
                <p className="text-xs text-slate-500">
                    {t("documents.labelHint")}
                </p>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="document-audience">{t("documents.audience")}</Label>
                <select
                    id="document-audience"
                    className="h-9 rounded-md border px-3 text-sm"
                    value={draft.audience_id ?? ""}
                    onChange={(event) => onChange({ audience_id: event.target.value || null })}
                >
                    <option value="">{t("documents.noAudience")}</option>
                    {audiences.map((audience: any) => (
                        <option key={audience.id} value={audience.id}>{audience.name}</option>
                    ))}
                </select>
            </div>
        </InspectorPanel>
    );
}

function TagsPanel({ selectedTagIds, tags, onChange }: any) {
    const { t } = useTranslation();
    const toggleTag = (tagId: any, checked: boolean) => {
        onChange({
            tag_ids: checked
                ? [...selectedTagIds, tagId]
                : selectedTagIds.filter((id: any) => id !== tagId),
        });
    };

    return (
        <InspectorPanel title={t("documents.tags")} icon={Tags}>
            {tags.length > 0 ? (
                <fieldset className="grid gap-2">
                    <legend className="sr-only">{t("documents.tags")}</legend>
                    {tags.map((tag: any) => (
                        <label key={tag.id} className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={selectedTagIds.includes(tag.id)}
                                onChange={(event) => toggleTag(tag.id, event.target.checked)}
                            />
                            {tag.name}
                        </label>
                    ))}
                </fieldset>
            ) : (
                <EmptyIntegrationHint>{t("documents.noTags")}</EmptyIntegrationHint>
            )}
        </InspectorPanel>
    );
}

function formatFileSize(byteSize: number): string {
    if (byteSize < 1024 * 1024) {
        return `${Math.max(1, Math.round(byteSize / 1024))} KB`;
    }

    return `${(byteSize / (1024 * 1024)).toFixed(1)} MB`;
}

function DownloadFileIcon({ filename, className = "size-4 text-slate-500" }: any) {
    const extension = filename.split(".").pop()?.toLowerCase();

    if (["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(extension)) {
        return <FileImage className={className} />;
    }

    if (["mp3", "wav", "ogg", "m4a"].includes(extension)) {
        return <FileAudio className={className} />;
    }

    if (["mp4", "mov", "webm", "avi"].includes(extension)) {
        return <FileVideo className={className} />;
    }

    if (["xls", "xlsx", "csv", "ods"].includes(extension)) {
        return <FileSpreadsheet className={className} />;
    }

    if (["doc", "docx", "odt", "rtf"].includes(extension)) {
        return <FileText className={className.replace("text-slate-500", "text-blue-600")} />;
    }

    if (["zip", "7z", "rar", "tar", "gz"].includes(extension)) {
        return <FileArchive className={className} />;
    }

    if (extension === "pdf") {
        return <FileWarning className={className.replace("text-slate-500", "text-red-600")} />;
    }

    return <FileText className={className} />;
}

function formatUploadDate(date: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(date));
}

function DownloadManager({ documentId, downloads, onChange }: any) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState("");
    const input = useRef<HTMLInputElement>(null);

    const upload = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setUploading(true);
        setError("");
        const formData = new FormData();
        formData.append("file", file);

        try {
            const response = await api.post(`/api/v1/admin/documents/${documentId}/downloads`, formData);
            onChange([...downloads, response.data.data]);
            input.current!.value = "";
        } catch (exception: any) {
            setError(exception.response?.data?.message ?? t("files.uploadError"));
        } finally {
            setUploading(false);
        }
    };
    const remove = async (download: any) => {
        setError("");

        try {
            await api.delete(`/api/v1/admin/documents/${documentId}/downloads/${download.id}`);
            onChange(downloads.filter((item: any) => item.id !== download.id));
        } catch (exception: any) {
            setError(exception.response?.data?.message ?? t("files.deleteError"));
        }
    };

    return (
        <>
            <Button variant="outline" size="sm" className="w-full" onClick={() => setOpen(true)}>
                <Upload /> {t("files.manage")}
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[85svh] max-w-2xl overflow-y-auto sm:max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>{t("files.manager")}</DialogTitle>
                        <DialogDescription>{t("files.description")}</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4">
                        <Input ref={input} type="file" onChange={upload} disabled={uploading} />
                        {uploading && <p className="text-sm text-slate-500">{t("files.uploading")}</p>}
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[42rem] text-left text-sm">
                                <thead className="border-b bg-slate-50 text-xs text-slate-500">
                                    <tr>
                                        <th className="w-10 px-3 py-2" aria-label={t("files.type")} />
                                        <th className="px-3 py-2 font-medium">{t("files.name")}</th>
                                        <th className="px-3 py-2 font-medium">{t("files.size")}</th>
                                        <th className="px-3 py-2 font-medium">{t("files.uploaded")}</th>
                                        <th className="px-3 py-2 text-right font-medium">{t("files.downloads")}</th>
                                        <th className="w-12 px-3 py-2" aria-label={t("files.actions")} />
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {downloads.map((download: any) => (
                                        <tr key={download.id}>
                                            <td className="px-3 py-2"><DownloadFileIcon filename={download.original_filename} /></td>
                                            <td className="max-w-52 px-3 py-2">
                                                <p className="truncate font-medium">{download.title}</p>
                                                <p className="truncate text-xs text-slate-500">{download.original_filename}</p>
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-2 text-slate-600">{formatFileSize(download.byte_size)}</td>
                                            <td className="whitespace-nowrap px-3 py-2 text-slate-600">{formatUploadDate(download.created_at, locale)}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{download.download_count}</td>
                                            <td className="px-3 py-2">
                                                <Button variant="ghost" size="icon-sm" onClick={() => remove(download)} title={t("files.delete")}>
                                                    <Trash2 className="size-4 text-red-600" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {downloads.length === 0 && (
                                        <tr><td colSpan={6} className="px-3 py-6 text-center text-slate-500">{t("files.empty")}</td></tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

function AttachmentsPanel({ draft, onChange }: any) {
    const { t } = useTranslation();
    const downloads = draft.downloads ?? [];

    return (
        <InspectorPanel title={t("files.title")} icon={Paperclip} defaultOpen>
            {downloads.length > 0 ? (
                <ul className="grid gap-2 text-sm">
                    {downloads.map((download: any) => (
                        <li key={download.id} className="flex items-center gap-2">
                            <DownloadFileIcon filename={download.original_filename} />
                            <span className="min-w-0 flex-1 truncate">{download.title}</span>
                            <span className="text-xs text-slate-500">{formatFileSize(download.byte_size)}</span>
                        </li>
                    ))}
                </ul>
            ) : <EmptyIntegrationHint>{t("files.noAttachments")}</EmptyIntegrationHint>}
            <DownloadManager documentId={draft.id} downloads={downloads} onChange={(downloads: any) => onChange({ downloads })} />
        </InspectorPanel>
    );
}

function AdvancedPanel({ draft, onChange }: any) {
    const { t } = useTranslation();
    return (
        <InspectorPanel title={t("documents.advanced")} icon={Settings}>
            <div className="grid gap-2">
                <Label htmlFor="document-icon">{t("documents.icon")}</Label>
                <Input
                    id="document-icon"
                    value={draft.icon ?? ""}
                    onChange={(event) => onChange({ icon: event.target.value })}
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="document-copyright">{t("documents.copyright")}</Label>
                <Input
                    id="document-copyright"
                    value={draft.copyright_notice ?? ""}
                    onChange={(event) => onChange({ copyright_notice: event.target.value })}
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="document-external-url">{t("documents.externalUrl")}</Label>
                <Input
                    id="document-external-url"
                    type="url"
                    value={draft.external_url ?? ""}
                    onChange={(event) => onChange({ external_url: event.target.value || null })}
                />
            </div>
        </InspectorPanel>
    );
}

function DocumentPreview({ draft, onClose }: { draft: any; onClose: () => void }) {
    const { t } = useTranslation();
    const editor = useCreateBlockNote({
        schema: documentSchema,
        initialContent: Array.isArray(draft.content) && draft.content.length > 0 ? draft.content : undefined,
    });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[85svh] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>{t("documents.preview")}</DialogTitle>
                    <DialogDescription>{t("documents.previewDescription")}</DialogDescription>
                </DialogHeader>
                <article className="mx-auto w-full max-w-4xl">
                    <h2 className="text-2xl font-semibold">{draft.title}</h2>
                    <BibleReferenceSelectionContext.Provider value={null}>
                        <BlockNoteView className="document-block-note" editor={editor} editable={false} />
                    </BibleReferenceSelectionContext.Provider>
                </article>
            </DialogContent>
        </Dialog>
    );
}

export function DocumentInspector({ draft, categories, audiences, tags, onDraftChange, onSave, onDelete, canDelete, canManagePublication, onBibleReferencesChange, saving, deleting, editor, selectedBoxId, inspectorContext, onInspectorContextChange }: any) {
    const { t } = useTranslation();
    const [previewOpen, setPreviewOpen] = useState(false);
    const updateDraft = (changes: any) => onDraftChange({ ...draft, ...changes });
    const canSave = Boolean(draft.title?.trim())
        && hasContent(draft.content ?? [])
        && (draft.status !== "published" || draft.published_at !== null);

    return (
        <aside className="border-l bg-white">
            <div className="flex flex-wrap items-center justify-end gap-2 px-4 py-3">
                <Button variant="outline" size="icon-lg" onClick={() => setPreviewOpen(true)} aria-label={t("documents.preview")} title={t("documents.preview")}>
                    <Eye aria-hidden="true" />
                </Button>
                <Button size="lg" onClick={onSave} disabled={saving || !canSave} title={!canSave ? t("documents.saveRequirements") : undefined}>
                    {t("actions.save")}
                </Button>
                {canDelete && <Button variant="destructive" size="lg" onClick={onDelete} disabled={deleting}>
                    {t("documents.delete")}
                </Button>}
            </div>
            {previewOpen && <DocumentPreview draft={draft} onClose={() => setPreviewOpen(false)} />}
            <Tabs.Root value={inspectorContext} onValueChange={onInspectorContextChange}>
            <Tabs.List activateOnFocus className="flex border-t border-b px-4" aria-label={t("documentBox.inspectorContext")}>
                <Tabs.Tab value="document" className="border-b-2 border-transparent px-3 py-2 text-sm font-medium text-slate-500 outline-none hover:text-slate-950 focus-visible:ring-2 focus-visible:ring-ring data-active:border-slate-900 data-active:text-slate-950">{t("documentBox.documentTab")}</Tabs.Tab>
                <Tabs.Tab value="block" disabled={!selectedBoxId} className="border-b-2 border-transparent px-3 py-2 text-sm font-medium text-slate-500 outline-none hover:text-slate-950 focus-visible:ring-2 focus-visible:ring-ring data-active:border-slate-900 data-active:text-slate-950 disabled:pointer-events-none disabled:opacity-40">{t("documentBox.blockTab")}</Tabs.Tab>
            </Tabs.List>
            <Tabs.Panel value="document">
            <DocumentPublicationPanel draft={draft} onChange={updateDraft} canManagePublication={canManagePublication} />
            <CoverImagePanel coverMediaId={draft.cover_media_id} onChange={(cover_media_id: any) => updateDraft({ cover_media_id })} />
            <AttachmentsPanel draft={draft} onChange={updateDraft} />
            <SummaryPanel summary={draft.summary} onChange={updateDraft} />
            <TaxonomyPanel draft={draft} categories={categories} audiences={audiences} onChange={updateDraft} />
            <TagsPanel selectedTagIds={draft.tag_ids ?? []} tags={tags} onChange={updateDraft} />
            <BibleReferencesPanel documentId={draft.id} references={draft.bible_references} onChanged={onBibleReferencesChange} />
            <AdvancedPanel draft={draft} onChange={updateDraft} />
            </Tabs.Panel>
            <Tabs.Panel value="block">{editor && selectedBoxId && editor.getBlock(selectedBoxId)?.type === "image" ? <ImageInspector editor={editor} blockId={selectedBoxId} /> : <BoxInspector editor={editor} boxId={selectedBoxId} />}</Tabs.Panel>
            </Tabs.Root>
        </aside>
    );
}
