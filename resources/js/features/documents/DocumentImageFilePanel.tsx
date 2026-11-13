import { useTranslation } from "@/i18n/I18nProvider";
import { FilePanelExtension } from "@blocknote/core/extensions";
import { useBlockNoteEditor, type FilePanelProps } from "@blocknote/react";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { MediaBrowser } from "@/features/documents/MediaBrowser";
import { documentSchema } from "./documentSchema";

export function DocumentImageFilePanel({ blockId }: FilePanelProps) {
    const { t } = useTranslation();
    const editor = useBlockNoteEditor(documentSchema);
    const block = editor.getBlock(blockId);
    const close = () => editor.getExtension(FilePanelExtension)?.closeMenu();

    if (block?.type !== "image") {
        return null;
    }

    return (
        <Dialog open onOpenChange={(open) => !open && close()}>
            <DialogContent className="max-h-[85svh] max-w-2xl overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>{t("media.title")}</DialogTitle>
                    <DialogDescription>{t("media.description")}</DialogDescription>
                </DialogHeader>
                <MediaBrowser
                    onSelect={(asset: { original_filename: string; url: string }) => {
                        editor.updateBlock(blockId, {
                            props: { name: asset.original_filename, url: asset.url },
                        });
                        close();
                    }}
                />
            </DialogContent>
        </Dialog>
    );
}
