import { useTranslation } from "@/i18n/I18nProvider";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useApiQuery } from "@/lib/api";
import { BibleBookCombobox } from "./BibleBookCombobox";
import { isConfiguredBibleReference, type BibleCatalog, type BibleReference, type BibleSchema } from "./bibleReference";

interface BibleReferenceDialogProps {
    reference: BibleReference;
    onConfirm: (reference: BibleReference) => void;
    onClose: () => void;
}

export function BibleReferenceDialog({ reference, onConfirm, onClose }: BibleReferenceDialogProps) {
    const { t } = useTranslation();
    const [bookSlug, setBookSlug] = useState("");
    const [chapter, setChapter] = useState("1");
    const [verse, setVerse] = useState("");
    const [endVerse, setEndVerse] = useState("");
    const translation = "de";
    const catalog = useApiQuery<BibleCatalog>(`/api/v1/admin/bible/books?translation=${translation}`);
    const schema = useApiQuery<BibleSchema>(`/api/v1/admin/bible/schema?translation=${translation}`);
    const books = [...(catalog.data?.books ?? [])].sort((first, second) => first.order - second.order);
    const selectedBook = books.find((book) => book.short_name === bookSlug);
    const verseCount = schema.data?.schema.find((book) => book.id === bookSlug)?.verses[chapter];
    const isConfigured = isConfiguredBibleReference(reference);

    useEffect(() => {
        if (isConfigured) {
            setBookSlug(reference.bookSlug);
            setChapter(String(reference.chapter));
            setVerse(String(reference.verse));
            setEndVerse(reference.endVerse ? String(reference.endVerse) : "");
        }
    }, [isConfigured, reference.bookSlug, reference.chapter, reference.verse, reference.endVerse]);

    useEffect(() => {
        if (!selectedBook) {
            return;
        }

        setChapter((value) => String(Math.min(Math.max(Number(value) || 1, 1), selectedBook.chapters)));
    }, [selectedBook]);

    const isValid = selectedBook && verseCount !== undefined
        && Number.isInteger(Number(chapter))
        && Number(chapter) >= 1
        && Number(chapter) <= selectedBook.chapters
        && Number.isInteger(Number(verse))
        && Number(verse) >= 1
        && Number(verse) <= verseCount
        && (!endVerse || (Number.isInteger(Number(endVerse)) && Number(endVerse) >= Number(verse) && Number(endVerse) <= verseCount));
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t("bible.select")}</DialogTitle>
                    <DialogDescription>{t("bible.description")}</DialogDescription>
                </DialogHeader>
                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (!isValid) {
                            return;
                        }

                        onConfirm({
                            translation,
                            bookSlug,
                            chapter: Number(chapter),
                            verse: Number(verse),
                            endVerse: endVerse ? Number(endVerse) : 0,
                        });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="bible-link-book">{t("bible.book")}</Label>
                        <BibleBookCombobox
                            id="bible-link-book"
                            books={books}
                            value={bookSlug}
                            onChange={setBookSlug}
                            disabled={catalog.isLoading || catalog.isError}
                        />
                        {catalog.isError && <p className="text-sm text-destructive">{t("bible.booksError")}</p>}
                        {schema.isError && <p className="text-sm text-destructive">{t("bible.schemaError")}</p>}
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="bible-link-chapter">{t("bible.chapter")}</Label>
                            <Input id="bible-link-chapter" type="number" min="1" max={selectedBook?.chapters} value={chapter} onChange={(event) => setChapter(event.target.value)} disabled={!selectedBook} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="bible-link-verse">{t("bible.verse")}</Label>
                            <Input id="bible-link-verse" type="number" min="1" max={verseCount} value={verse} onChange={(event) => {
                                const nextVerse = event.target.value;
                                setVerse(nextVerse);
                                setEndVerse((current) => current && Number(current) < Number(nextVerse) ? nextVerse : current);
                            }} disabled={!selectedBook || verseCount === undefined} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="bible-link-end-verse">{t("bible.endVerse")}</Label>
                            <Input id="bible-link-end-verse" type="number" min={Math.max(1, Number(verse) || 1)} max={verseCount} value={endVerse} onChange={(event) => setEndVerse(event.target.value)} disabled={!selectedBook || verseCount === undefined} />
                        </div>
                    </div>
                    {verseCount !== undefined && <p className="text-sm text-muted-foreground">{t("bible.verseLimit", { count: verseCount })}</p>}
                    <div className="flex justify-end gap-2">
                        <Button type="submit" disabled={!isValid}>{t("actions.apply")}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
