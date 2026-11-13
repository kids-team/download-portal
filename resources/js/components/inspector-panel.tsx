import { useState } from "react";
import { ChevronRight } from "lucide-react";

export function InspectorPanel({ title, icon: Icon, defaultOpen = false, children }: any) {
    const storageKey = `downloadportal-inspector-${title}`;
    const [open, setOpen] = useState(
        () => localStorage.getItem(storageKey) === "true" || defaultOpen,
    );

    const toggle = () => {
        localStorage.setItem(storageKey, open ? "" : "true");
        setOpen(!open);
    };

    return (
        <section className="border-t">
            <button
                type="button"
                onClick={toggle}
                className="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold hover:bg-blue-50"
            >
                <span className="flex items-center gap-2">
                    <Icon className="size-4 text-slate-400" />
                    {title}
                </span>
                <ChevronRight
                    className={`size-4 transition-transform ${open ? "rotate-90" : ""}`}
                />
            </button>
            {open && <div className="grid gap-4 border-t px-4 py-4">{children}</div>}
        </section>
    );
}
