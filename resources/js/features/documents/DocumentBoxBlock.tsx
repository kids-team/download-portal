import { createReactBlockSpec } from "@blocknote/react";

export const DocumentBoxBlock = createReactBlockSpec(
    {
        type: "box",
        propSchema: {
            backgroundColor: { default: "#f1f5f9" },
            textColor: { default: "#0f172a" },
            borderColor: { default: "#cbd5e1" },
            borderWidth: { default: 1 },
            padding: { default: 16 },
            marginTop: { default: 0 },
            marginBottom: { default: 0 },
            radiusTopLeft: { default: 6 },
            radiusTopRight: { default: 6 },
            radiusBottomRight: { default: 6 },
            radiusBottomLeft: { default: 6 },
        },
        content: "none",
        layoutOnly: true,
    },
    {
        renderFrame: ({ slotRef, block }) => (
            <div ref={slotRef} className="document-box" style={{
                backgroundColor: block.props.backgroundColor,
                color: block.props.textColor,
                border: `${block.props.borderWidth}px solid ${block.props.borderColor}`,
                padding: block.props.padding,
                marginTop: block.props.marginTop,
                marginBottom: block.props.marginBottom,
                borderRadius: `${block.props.radiusTopLeft}px ${block.props.radiusTopRight}px ${block.props.radiusBottomRight}px ${block.props.radiusBottomLeft}px`,
            }} />
        ),
    },
);
