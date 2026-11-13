import { useTranslation } from "@/i18n/I18nProvider";
import { useRef, useState } from "react";
import { Search, Upload } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api, useApiQuery } from "@/lib/api";

export function MediaBrowser({ onSelect, selectedMediaId = null }: any) {
    const { t } = useTranslation();
    const [search, setSearch] = useState("");
    const [uploading, setUploading] = useState(false);
    const input = useRef<HTMLInputElement>(null);
    const media = useApiQuery("/api/v1/admin/media-assets");
    const assets = media.data ?? [];
    const normalizedSearch = search.trim().toLocaleLowerCase("de");
    const visibleAssets = assets.filter((asset: any) => asset.original_filename.toLocaleLowerCase("de").includes(normalizedSearch));

    const upload = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append("file", file);

        try {
            const response = await api.post("/api/v1/admin/media-assets", formData);
            onSelect(response.data.data);
        } finally {
            input.current!.value = "";
            setUploading(false);
        }
    };

    return (
        <div className="grid gap-3">
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-2.5 size-4 text-slate-400" />
                    <Input className="pl-9" placeholder={t("media.search")} value={search} onChange={(event: React.ChangeEvent<HTMLInputElement>) => setSearch(event.target.value)} />
                </div>
                <Button type="button" variant="outline" onClick={() => input.current?.click()} disabled={uploading}>
                    <Upload /> {t("actions.upload")}
                </Button>
                <input ref={input} type="file" accept="image/jpeg,image/png,image/gif,image/webp" className="hidden" onChange={upload} disabled={uploading} />
            </div>
            {uploading && <p className="text-sm text-slate-500">{t("media.uploading")}</p>}
            <div className="grid max-h-80 grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-3 lg:grid-cols-4">
                {visibleAssets.map((asset: any) => (
                    <button key={asset.id} type="button" className={`overflow-hidden rounded-md border text-left ${asset.id === selectedMediaId ? "ring-2 ring-blue-600" : "hover:ring-2 hover:ring-blue-300"}`} onClick={() => onSelect(asset)}>
                        {asset.url && <img src={asset.url} alt="" className="aspect-video w-full object-cover" />}
                        <span className="block truncate px-2 py-1 text-xs">{asset.original_filename}</span>
                    </button>
                ))}
            </div>
        </div>
    );
}
