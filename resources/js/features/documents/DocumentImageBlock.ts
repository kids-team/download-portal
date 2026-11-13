import { defaultBlockSpecs, type BlockImplementation } from "@blocknote/core";

const original = defaultBlockSpecs.image;
const config = {
    ...original.config,
    propSchema: {
        ...original.config.propSchema,
        borderColor: { default: "#cbd5e1" },
        borderWidth: { default: 0 },
        radiusTopLeft: { default: 0 },
        radiusTopRight: { default: 0 },
        radiusBottomRight: { default: 0 },
        radiusBottomLeft: { default: 0 },
        href: { default: "" },
    },
};

type ImageProps = {
    borderColor: string;
    borderWidth: number;
    radiusTopLeft: number;
    radiusTopRight: number;
    radiusBottomRight: number;
    radiusBottomLeft: number;
    href: string;
};

function styleImage(image: HTMLElement, props: ImageProps) {
    image.style.border = `${props.borderWidth}px solid ${props.borderColor}`;
    image.style.borderRadius = `${props.radiusTopLeft}px ${props.radiusTopRight}px ${props.radiusBottomRight}px ${props.radiusBottomLeft}px`;
}

function safeLink(href: string): string | null {
    const value = href.trim();
    if (!value || /[\u0000-\u001f\u007f]/.test(value)) return null;
    try {
        const url = new URL(value, window.location.origin);
        return ["http:", "https:", "mailto:"].includes(url.protocol) ? value : null;
    } catch {
        return null;
    }
}

const implementation: BlockImplementation<"image", typeof config.propSchema, "none"> = {
    meta: original.implementation.meta,
    parse: original.implementation.parse,
    runsBefore: original.implementation.runsBefore,
    render(block, editor) {
        const output = Reflect.apply(original.implementation.render!, this, [block, editor]);
        const image = output.dom.querySelector("img");
        if (image) styleImage(image, block.props);
        return { ...output, update: () => false };
    },
    toExternalHTML(block, editor, context) {
        const output = Reflect.apply(original.implementation.toExternalHTML!, this, [block, editor, context]);
        if (!output) return output;
        const image = output.dom instanceof HTMLImageElement ? output.dom : output.dom.querySelector("img");
        if (image) {
            styleImage(image, block.props);
            const href = safeLink(block.props.href);
            if (href) {
                const link = document.createElement("a");
                link.setAttribute("href", href);
                if (image === output.dom) {
                    link.appendChild(image);
                    return { ...output, dom: link };
                }
                image.replaceWith(link);
                link.appendChild(image);
            }
        }
        return output;
    },
};

export const DocumentImageBlock = { ...original, config, implementation };
