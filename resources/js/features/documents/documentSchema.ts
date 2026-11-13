import { BlockNoteSchema, defaultBlockSpecs, defaultInlineContentSpecs } from "@blocknote/core";
import { BibleReferenceInlineContent } from "./BibleReferenceInlineContent";
import { DocumentBoxBlock } from "./DocumentBoxBlock";
import { DocumentImageBlock } from "./DocumentImageBlock";

export const documentSchema = BlockNoteSchema.create({
    blockSpecs: { ...defaultBlockSpecs, image: DocumentImageBlock, box: DocumentBoxBlock() },
    inlineContentSpecs: { ...defaultInlineContentSpecs, bibleReference: BibleReferenceInlineContent },
});

export type DocumentBlock = typeof documentSchema.Block;
export type PartialDocumentBlock = typeof documentSchema.PartialBlock;
