import { FileText, LoaderCircle } from "lucide-react";
import { BlockNoteEditor } from "@/features/documents/BlockNoteEditor";
import { useTranslation } from "@/i18n/I18nProvider";
import { slugify } from "@/lib/slugify";

export function DocumentContent({ selectedId, document, draft, onDraftChange, onEditorChange, onBoxSelection }: any) {
    const { t } = useTranslation();
    if (!selectedId) {
        return (
            <main className="grid h-full place-items-center p-6 text-slate-500">
                <div className="text-center">
                    <FileText className="mx-auto mb-3 size-10" />
                    {t("documents.select")}
                </div>
            </main>
        );
    }

    if (document.isLoading || !draft) {
        return <main className="p-6"><LoaderCircle className="animate-spin" /></main>;
    }

    const isUntitled = draft.title === t("documents.untitled");

    return (
        <main className="p-6">
            <div className="mx-auto grid w-full max-w-4xl gap-5">
                <header>
                    <h1>
                        <input
                            aria-label={t("documents.title")}
                            className="h-auto w-full border-0 bg-transparent p-0 text-2xl font-semibold shadow-none outline-none placeholder:text-slate-400 focus-visible:ring-0"
                            placeholder={t("documents.titlePlaceholder")}
                            value={isUntitled ? "" : (draft.title ?? "")}
                            onChange={(event) => onDraftChange({ ...draft, title: event.target.value, slug: slugify(event.target.value) })}
                        />
                    </h1>

                </header>
                <BlockNoteEditor key={draft.id} content={draft.content} onEditorChange={onEditorChange} onBoxSelection={onBoxSelection} onChange={(content: any) => onDraftChange({ ...draft, content })} />
            </div>
        </main>
    );
}
