import { FolderTree, Settings, Tags, Users } from "lucide-react";
import { useTranslation } from "@/i18n/I18nProvider";

export function ManagementSidebar({ activeSection, onSelect }: any) {
    const { t } = useTranslation();
    const sections = [
        { id: "categories", label: t("management.categories.title"), icon: FolderTree },
        { id: "audiences", label: t("management.audiences.title"), icon: Users },
        { id: "tags", label: t("management.tags.title"), icon: Tags },
        { id: "users", label: t("management.users.title"), icon: Users },
        { id: "settings", label: t("management.settings.title"), icon: Settings },
    ];

    return (
        <nav className="border-b bg-white p-3 lg:min-h-[calc(100svh-57px)] lg:border-b-0 lg:border-r" aria-label={t("management.title")}>
            <p className="px-3 pb-2 pt-1 text-xs font-medium uppercase tracking-wide text-slate-500">{t("management.title")}</p>
            <div className="flex gap-1 overflow-x-auto lg:flex-col">
                {sections.map(({ id, label, icon: Icon }) => (
                    <button
                        key={id}
                        type="button"
                        onClick={() => onSelect(id)}
                        className={`flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-colors ${activeSection === id ? "bg-slate-100 font-medium text-slate-950" : "text-slate-600 hover:bg-slate-50 hover:text-slate-950"}`}
                    >
                        <Icon className="size-4" />
                        {label}
                    </button>
                ))}
            </div>
        </nav>
    );
}
