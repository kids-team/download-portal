import { useTranslation } from "@/i18n/I18nProvider";
import { useCallback } from "react";
import { filterSuggestionItems, insertOrUpdateBlockForSlashMenu } from "@blocknote/core";
import { getDefaultReactSlashMenuItems, SuggestionMenuController, useBlockNoteEditor } from "@blocknote/react";
import { BookOpen, Square } from "lucide-react";
import { documentSchema } from "./documentSchema";

export function DocumentSlashMenu() {
    const { t } = useTranslation();
    const editor = useBlockNoteEditor(documentSchema);
    const getItems = useCallback(async (query: string) => filterSuggestionItems([
        ...getDefaultReactSlashMenuItems(editor),
        {
            title: t("documentBox.title"),
            subtext: t("documentBox.description"),
            aliases: ["box", "gruppe", "group", "callout"],
            group: t("bible.slashGroup"),
            icon: <Square size={18} />,
            onItemClick: () => insertOrUpdateBlockForSlashMenu(editor, { type: "box" }),
        },
        {
            title: t("bible.reference"),
            subtext: t("bible.insertDescription"),
            aliases: ["bibel", "vers"],
            group: t("bible.slashGroup"),
            icon: <BookOpen size={18} />,
            onItemClick: () => {
                editor.insertInlineContent([{
                    type: "bibleReference",
                    props: { translation: "de", bookSlug: "", chapter: 0, verse: 0, endVerse: 0 },
                }]);
            },
        },
    ], query), [editor, t]);

    return <SuggestionMenuController triggerCharacter="/" getItems={getItems} />;
}
