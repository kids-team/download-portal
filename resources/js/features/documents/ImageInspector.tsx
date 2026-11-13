import { useTranslation } from "@/i18n/I18nProvider";
import { Input } from "@/components/ui/input";
import type { DocumentEditor } from "./BlockNoteEditor";

const radiusValues = [0, 4, 6, 8, 12, 16, 24];

export function ImageInspector({ editor, blockId }: { editor: DocumentEditor; blockId: string }) {
    const { t } = useTranslation();
    const block = editor.getBlock(blockId);
    if (!block || block.type !== "image") return null;

    const props = block.props;
    const update = (changes: Partial<typeof props>) => editor.updateBlock(block.id, { type: "image", props: changes });
    const corners = [props.radiusTopLeft, props.radiusTopRight, props.radiusBottomRight, props.radiusBottomLeft];
    const commonRadius = corners.every((value) => value === corners[0]) ? String(corners[0]) : "";
    const numberSelect = (label: string, value: number | string, values: number[], onChange: (value: number) => void) => (
        <label className="grid gap-1 text-sm">
            {label}
            <select className="h-9 rounded-md border bg-white px-2" value={String(value)} onChange={(event) => onChange(Number(event.target.value))}>
                {value === "" && <option value="" disabled>{t("documentBox.mixed")}</option>}
                {values.map((option) => <option key={option} value={option}>{option} px</option>)}
            </select>
        </label>
    );

    return (
        <div className="grid gap-5 border-t p-4">
            <h2 className="font-semibold">{t("documentImage.design")}</h2>
            <fieldset className="grid gap-3">
                <legend className="mb-2 text-sm font-semibold">{t("documentImage.border")}</legend>
                <label className="flex items-center justify-between gap-3 text-sm">
                    {t("documentBox.borderColor")}
                    <input className="h-9 w-14 cursor-pointer rounded border bg-white p-1" type="color" value={props.borderColor} onChange={(event) => update({ borderColor: event.target.value })} />
                </label>
                {numberSelect(t("documentBox.borderWidth"), props.borderWidth, [0, 1, 2, 4], (borderWidth) => update({ borderWidth }))}
            </fieldset>
            <fieldset className="grid gap-3">
                <legend className="mb-2 text-sm font-semibold">{t("documentBox.corners")}</legend>
                {numberSelect(t("documentBox.allCorners"), commonRadius, radiusValues, (radius) => update({ radiusTopLeft: radius, radiusTopRight: radius, radiusBottomRight: radius, radiusBottomLeft: radius }))}
                <details>
                    <summary className="cursor-pointer text-sm">{t("documentBox.individualCorners")}</summary>
                    <div className="mt-3 grid grid-cols-2 gap-3">
                        {numberSelect(t("documentBox.topLeft"), props.radiusTopLeft, radiusValues, (radiusTopLeft) => update({ radiusTopLeft }))}
                        {numberSelect(t("documentBox.topRight"), props.radiusTopRight, radiusValues, (radiusTopRight) => update({ radiusTopRight }))}
                        {numberSelect(t("documentBox.bottomLeft"), props.radiusBottomLeft, radiusValues, (radiusBottomLeft) => update({ radiusBottomLeft }))}
                        {numberSelect(t("documentBox.bottomRight"), props.radiusBottomRight, radiusValues, (radiusBottomRight) => update({ radiusBottomRight }))}
                    </div>
                </details>
            </fieldset>
            <label className="grid gap-1 text-sm">
                {t("documentImage.link")}
                <Input type="url" placeholder="https://" value={props.href} onChange={(event) => update({ href: event.target.value })} />
            </label>
        </div>
    );
}
