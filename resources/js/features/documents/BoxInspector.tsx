import { useTranslation } from "@/i18n/I18nProvider";
import type { DocumentEditor } from "./BlockNoteEditor";

const spacingValues = [0, 4, 8, 12, 16, 24, 32, 48];
const radiusValues = [0, 4, 6, 8, 12, 16, 24];

export function BoxInspector({ editor, boxId }: { editor: DocumentEditor | null; boxId: string | null }) {
    const { t } = useTranslation();
    const block = boxId ? editor?.getBlock(boxId) : undefined;

    if (!editor || !block || block.type !== "box") {
        return <p className="p-4 text-sm text-slate-500">{t("documentBox.selectBox")}</p>;
    }

    const props = block.props;
    const update = (changes: Partial<typeof props>) => editor.updateBlock(block.id, { type: "box", props: changes });
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
    const colorInput = (label: string, value: string, onChange: (value: string) => void) => (
        <label className="flex items-center justify-between gap-3 text-sm">
            {label}
            <input className="h-9 w-14 cursor-pointer rounded border bg-white p-1" type="color" value={value} onChange={(event) => onChange(event.target.value)} />
        </label>
    );

    return (
        <div className="grid gap-5 border-t p-4">
            <h2 className="font-semibold">{t("documentBox.design")}</h2>
            <fieldset className="grid gap-3">
                <legend className="mb-2 text-sm font-semibold">{t("documentBox.colors")}</legend>
                {colorInput(t("documentBox.background"), props.backgroundColor, (backgroundColor) => update({ backgroundColor }))}
                {colorInput(t("documentBox.foreground"), props.textColor, (textColor) => update({ textColor }))}
                {colorInput(t("documentBox.borderColor"), props.borderColor, (borderColor) => update({ borderColor }))}
                {numberSelect(t("documentBox.borderWidth"), props.borderWidth, [0, 1, 2, 4], (borderWidth) => update({ borderWidth }))}
            </fieldset>
            <fieldset className="grid gap-3">
                <legend className="mb-2 text-sm font-semibold">{t("documentBox.spacing")}</legend>
                {numberSelect(t("documentBox.padding"), props.padding, spacingValues, (padding) => update({ padding }))}
                {numberSelect(t("documentBox.marginTop"), props.marginTop, spacingValues, (marginTop) => update({ marginTop }))}
                {numberSelect(t("documentBox.marginBottom"), props.marginBottom, spacingValues, (marginBottom) => update({ marginBottom }))}
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
        </div>
    );
}
