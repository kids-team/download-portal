import { useTranslation } from "@/i18n/I18nProvider";
import { useEffect, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { BookOpen, Plus, X } from "lucide-react";
import { InspectorPanel } from "@/components/inspector-panel";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { api, useApiQuery } from "@/lib/api";

const translation = "de";

function referenceLabel(reference: any, books: any[]) {
    const book = books.find((candidate) => candidate.short_name === reference.book_slug);
    const location = reference.start_verse
        ? `${reference.start_chapter}:${reference.start_verse}`
        : reference.start_chapter;

    return `${book?.long_name ?? reference.book_slug} ${location}`;
}

export function BibleReferencesPanel({ documentId, references, onChanged }: any) {
    const { t } = useTranslation();
    const cache = useQueryClient();
    const catalog = useApiQuery(`/api/v1/admin/bible/books?translation=${translation}`);
    const books = [...(catalog.data?.books ?? [])].sort((first, second) => first.order - second.order);
    const [bookSlug, setBookSlug] = useState("");
    const [chapter, setChapter] = useState("1");
    const selectedBook = books.find((book) => book.short_name === bookSlug);

    useEffect(() => {
        if (!selectedBook) {
            setChapter("1");
            return;
        }

        setChapter((value) => String(Math.min(Math.max(Number(value) || 1, 1), selectedBook.chapters)));
    }, [selectedBook]);

    const refresh = () => {
        cache.invalidateQueries({ queryKey: [`/api/v1/admin/documents/${documentId}`] });
        onChanged();
    };
    const add = useMutation({
        mutationFn: () => api.post(`/api/v1/admin/documents/${documentId}/bible-references`, {
            translation,
            book_slug: bookSlug,
            start_chapter: Number(chapter),
        }),
        onSuccess: refresh,
    });
    const remove = useMutation({
        mutationFn: (referenceId) => api.delete(`/api/v1/admin/documents/${documentId}/bible-references/${referenceId}`),
        onSuccess: refresh,
    });
    const isChapterValid = selectedBook && Number(chapter) >= 1 && Number(chapter) <= selectedBook.chapters;

    return (
        <InspectorPanel title={t("bible.references")} icon={BookOpen}>
            {references?.length > 0 && (
                <ul className="mb-3 flex flex-wrap gap-2">
                    {references.map((reference: any) => (
                        <li key={reference.id} className="flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 py-1 pl-2 pr-1 text-sm text-blue-900">
                            {referenceLabel(reference, books)}
                            <button
                                type="button"
                                className="rounded p-0.5 text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
                                aria-label={t("bible.remove", { reference: referenceLabel(reference, books) })}
                                onClick={() => remove.mutate(reference.id)}
                                disabled={remove.isPending}
                            >
                                <X className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <div className="grid gap-2">
                <Label htmlFor="bible-book">{t("bible.book")}</Label>
                <select
                    id="bible-book"
                    className="h-9 rounded-md border bg-white px-3 text-sm"
                    value={bookSlug}
                    onChange={(event) => setBookSlug(event.target.value)}
                    disabled={catalog.isLoading || catalog.isError}
                >
                    <option value="">{t("bible.selectBook")}</option>
                    {books.map((book) => (
                        <option key={book.id} value={book.short_name}>{book.long_name}</option>
                    ))}
                </select>
                <Label htmlFor="bible-chapter">{t("bible.chapter")}</Label>
                <div className="flex gap-2">
                    <input
                        id="bible-chapter"
                        type="number"
                        min="1"
                        max={selectedBook?.chapters}
                        className="h-9 min-w-0 flex-1 rounded-md border px-3 text-sm"
                        value={chapter}
                        onChange={(event) => setChapter(event.target.value)}
                        disabled={!selectedBook}
                    />
                    <Button type="button" size="icon" onClick={() => add.mutate()} disabled={!isChapterValid || add.isPending} aria-label={t("bible.add")}>
                        <Plus className="size-4" />
                    </Button>
                </div>
                {catalog.isError && <p className="text-sm text-red-600">{t("bible.loadError")}</p>}
                {!catalog.isLoading && !catalog.isError && books.length === 0 && <p className="text-sm text-slate-500">{t("bible.noTranslation")}</p>}
                {add.isError && <p className="text-sm text-red-600">{t("bible.saveError")}</p>}
            </div>
        </InspectorPanel>
    );
}
