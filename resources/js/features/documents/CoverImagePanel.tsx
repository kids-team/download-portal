import { useTranslation } from "@/i18n/I18nProvider";
import { useState } from "react";
import { Image, Trash2 } from "lucide-react";
import { InspectorPanel } from "@/components/inspector-panel";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useApiQuery } from "@/lib/api";
import { MediaBrowser } from "@/features/documents/MediaBrowser";

export function CoverImagePanel({ coverMediaId, onChange }: any) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const current = useApiQuery(`/api/v1/admin/media-assets/${coverMediaId}`, { enabled: coverMediaId !== null });

    return (
        <InspectorPanel title={t("media.cover")} icon={Image}>
            <button type="button" className="block w-full overflow-hidden rounded-md text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" onClick={() => setOpen(true)}>
                {current.data?.url ? (
                    <img src={current.data.url} alt={t("media.currentCover")} className="aspect-video w-full object-cover" />
                ) : (
                    <span className="grid aspect-video place-items-center border border-dashed bg-slate-50 text-sm text-slate-500">{t("media.noCover")}</span>
                )}
            </button>
            {coverMediaId !== null && <div className="flex justify-end">
                <Button variant="outline" size="icon-sm" title={t("media.removeCover")} onClick={() => onChange(null)}><Trash2 className="text-red-600" /></Button>
            </div>}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[85svh] max-w-2xl overflow-y-auto sm:max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>{t("media.title")}</DialogTitle>
                        <DialogDescription>{t("media.description")}</DialogDescription>
                    </DialogHeader>
                    <MediaBrowser selectedMediaId={coverMediaId} onSelect={(asset: any) => { onChange(asset.id); setOpen(false); }} />
                </DialogContent>
            </Dialog>
        </InspectorPanel>
    );
}
