import "@blocknote/core/fonts/inter.css";
import "@blocknote/shadcn/style.css";
import { FilePanelController, useCreateBlockNote } from "@blocknote/react";
import { BlockNoteView } from "@blocknote/shadcn";
import { DocumentImageFilePanel } from "./DocumentImageFilePanel";
import { DocumentSlashMenu } from "./DocumentSlashMenu";
import { documentSchema, type DocumentBlock, type PartialDocumentBlock } from "./documentSchema";
import { useEffect } from "react";

export type DocumentEditor = typeof documentSchema.BlockNoteEditor;

interface BlockNoteEditorProps {
    content: PartialDocumentBlock[] | null;
    onChange: (content: DocumentBlock[]) => void;
    onEditorChange: (editor: DocumentEditor | null) => void;
    onBoxSelection: (boxId: string | null) => void;
}

export function BlockNoteEditor({ content, onChange, onEditorChange, onBoxSelection }: BlockNoteEditorProps) {
    const editor = useCreateBlockNote({
        schema: documentSchema,
        initialContent: Array.isArray(content) && content.length > 0 ? content : undefined,
    });

    useEffect(() => {
        onEditorChange(editor);
        return () => onEditorChange(null);
    }, [editor, onEditorChange]);

    const updateSelection = () => {
        const selection = editor.getSelection();
        let block: DocumentBlock | undefined = selection?.blocks.length === 1 && selection.blocks[0].type === "image"
            ? selection.blocks[0]
            : editor.getTextCursorPosition().block;
        while (block && block.type !== "box" && block.type !== "image") {
            block = editor.getParentBlock(block);
        }
        onBoxSelection(block?.id ?? null);
    };

    return (
        <BlockNoteView className="document-block-note min-h-96" editor={editor} filePanel={false} slashMenu={false} onSelectionChange={updateSelection} onChange={() => onChange(editor.document)}>
            <FilePanelController filePanel={DocumentImageFilePanel} />
            <DocumentSlashMenu />
        </BlockNoteView>
    );
}
