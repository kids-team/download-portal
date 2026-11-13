import { useState } from "react";
import { BookText, LayoutDashboard, LogOut, Settings, UserRound } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Dashboard } from "@/features/dashboard/Dashboard";
import { DocumentsWorkspace } from "@/features/documents/DocumentsWorkspace";
import { ManagementWorkspace } from "@/features/management/ManagementWorkspace";
import { ProfileWorkspace } from "@/features/profile/ProfileWorkspace";
import { useTranslation } from "@/i18n/I18nProvider";

export function AdminShell({ user, onUserChange, onLogout }: any) {
    const { t } = useTranslation();
    const canManage = user.role === "admin";
    const [page, setPage] = useState(() => {
        if (location.pathname.includes("dashboard")) {
            return "dashboard";
        }

        if (location.pathname.includes("profile")) {
            return "profile";
        }

        return canManage && location.pathname.includes("management") ? "management" : "documents";
    });
    const goTo = (nextPage: string) => {
        history.pushState({}, "", `/admin/${nextPage}`);
        setPage(nextPage);
    };

    return (
        <>
            <header className="flex h-14 items-center gap-3 border-b bg-white px-4">
                <strong className="mr-auto">{t("app.name")}</strong>
                <Button variant={page === "dashboard" ? "secondary" : "ghost"} onClick={() => goTo("dashboard")}><LayoutDashboard /> {t("navigation.dashboard")}</Button>
                <Button variant={page === "documents" ? "secondary" : "ghost"} onClick={() => goTo("documents")}><BookText /> {t("navigation.documents")}</Button>
                {canManage && <Button variant={page === "management" ? "secondary" : "ghost"} onClick={() => goTo("management")}><Settings /> {t("navigation.management")}</Button>}
                <Button variant={page === "profile" ? "secondary" : "ghost"} onClick={() => goTo("profile")}><UserRound /> {user.name}</Button>
                <Button variant="ghost" size="icon" onClick={onLogout} title={t("navigation.logout")}><LogOut /></Button>
            </header>
            {page === "dashboard" && <Dashboard />}
            {page === "documents" && <DocumentsWorkspace user={user} />}
            {page === "management" && canManage && <ManagementWorkspace />}
            {page === "profile" && <ProfileWorkspace user={user} onUserChange={onUserChange} />}
        </>
    );
}
