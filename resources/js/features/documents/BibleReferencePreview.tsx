import { useTranslation } from "@/i18n/I18nProvider";
import { useApiQuery } from "@/lib/api";
import type { BibleReference, BibleVerseResponse } from "./bibleReference";

export function BibleReferencePreview({ reference }: { reference: BibleReference }) {
    const { t } = useTranslation();
    const location = `${reference.bookSlug}/${reference.chapter}:${reference.verse}${reference.endVerse ? `-${reference.endVerse}` : ""}`;
    const preview = useApiQuery<BibleVerseResponse>(
        `/api/v1/admin/bible/verses?reference=${encodeURIComponent(location)}&translation=${encodeURIComponent(reference.translation)}`,
    );
    const verses = preview.data?.verses ?? [];

    return (
        <span className="absolute left-0 top-full z-20 mt-2 block w-80 rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-lg">
            {preview.isLoading && <span className="block text-slate-500">{t("bible.loadingText")}</span>}
            {preview.isError && <span className="block text-red-600">{t("bible.textError")}</span>}
            {preview.data && verses.length === 0 && <span className="block text-slate-500">{t("bible.noVerses")}</span>}
            {verses.length > 0 && <span className="block leading-6 text-slate-900">{verses.map((item) => item.text).join(" ")}</span>}
        </span>
    );
}
