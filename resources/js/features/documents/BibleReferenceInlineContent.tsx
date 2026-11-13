import { useTranslation } from "@/i18n/I18nProvider";
import { useEffect, useRef, useState } from "react";
import { BookOpen } from "lucide-react";
import type { DefaultStyleSchema } from "@blocknote/core";
import { createReactInlineContentSpec, type ReactCustomInlineContentRenderProps } from "@blocknote/react";
import { useBibleReferenceSelection } from "./BibleReferenceSelection";
import { BibleReferencePreview } from "./BibleReferencePreview";
import { useApiQuery } from "@/lib/api";
import { bibleReferenceConfig, isConfiguredBibleReference, type BibleCatalog } from "./bibleReference";

type BibleReferenceInlineContentProps = ReactCustomInlineContentRenderProps<typeof bibleReferenceConfig, DefaultStyleSchema>;

function BibleReferenceInlineContentView({ inlineContent, updateInlineContent, editor }: BibleReferenceInlineContentProps) {
    const { t } = useTranslation();
    const reference = inlineContent.props;
    const selection = useBibleReferenceSelection();
    const id = useRef(Symbol("bibleReference"));
    const [hovered, setHovered] = useState(false);
    const isConfigured = isConfiguredBibleReference(reference);
    const select = () => selection?.select({
        id: id.current,
        reference,
        update: (props) => updateInlineContent({ type: "bibleReference", props }),
    });
    const selectionRef = useRef(selection);
    selectionRef.current = selection;
    useEffect(() => {
        if (!isConfigured && editor.isFocused()) {
            select();
        }
        return () => selectionRef.current?.clear(id.current);
    }, []);
    const catalog = useApiQuery<BibleCatalog>(
        `/api/v1/admin/bible/books?translation=${encodeURIComponent(reference.translation)}`,
        { enabled: isConfigured },
    );
    const bookName = catalog.data?.books.find((book) => book.short_name === reference.bookSlug)?.long_name
        ?? reference.bookSlug.replaceAll("-", " ");
    const label = isConfigured
        ? `${bookName} ${reference.chapter},${reference.verse}${reference.endVerse ? `–${reference.endVerse}` : ""}`
        : t("bible.select");

    return (
        <span
            className="relative"
            contentEditable={false}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
        >
            <button
                type="button"
                className="inline-flex items-baseline gap-1.5 px-2 py-1 text-left align-baseline text-inherit font-[inherit] transition-colors hover:bg-slate-100"
                onClick={select}
            >
                <BookOpen className="size-4 text-slate-500 translate-y-[3px]" aria-hidden="true" />
                <span><span className="text-slate-950">{label}</span></span>
            </button>
            {hovered && isConfigured && <BibleReferencePreview reference={reference} />}
        </span>
    );
}

export const BibleReferenceInlineContent = createReactInlineContentSpec(
    bibleReferenceConfig,
    { render: BibleReferenceInlineContentView },
);
