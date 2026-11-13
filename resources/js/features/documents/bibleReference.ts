import type { Props } from "@blocknote/core";

export const bibleReferenceConfig = {
    type: "bibleReference",
    propSchema: {
        translation: { default: "de" },
        bookSlug: { default: "" },
        chapter: { default: 0 },
        verse: { default: 0 },
        endVerse: { default: 0 },
    },
    content: "none",
} as const;

export type BibleReference = Props<typeof bibleReferenceConfig.propSchema>;

export interface BibleBook {
    id: number;
    short_name: string;
    long_name: string;
    order: number;
    chapters: number;
}

export interface BibleCatalog {
    books: BibleBook[];
}

export interface BibleSchema {
    schema: { id: string; verses: Record<string, number> }[];
}

export interface BibleVerseResponse {
    verses: { text: string }[] | null;
}

export function isConfiguredBibleReference(reference: BibleReference): boolean {
    return Boolean(reference.bookSlug && reference.chapter && reference.verse);
}
