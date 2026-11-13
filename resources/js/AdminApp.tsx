import { useEffect, useState } from "react";
import { LoaderCircle } from "lucide-react";
import { LoginForm } from "@/features/auth/LoginForm";
import { AdminShell } from "@/layouts/AdminShell";
import { api } from "@/lib/api";
import { useTranslation } from "@/i18n/I18nProvider";

export function AdminApp() {
    const [user, setUser] = useState<any | null | undefined>(undefined);
    const { setLocale } = useTranslation();

    useEffect(() => {
        api.get("/api/v1/admin/me").then((response) => setUser(response.data.data)).catch(() => setUser(null));
    }, []);

    useEffect(() => {
        if (user?.locale) {
            setLocale(user.locale);
        }
    }, [setLocale, user]);

    if (user === undefined) {
        return <main className="grid min-h-svh place-items-center"><LoaderCircle className="animate-spin" /></main>;
    }

    if (!user) {
        return <LoginForm onLogin={setUser} />;
    }

    return <AdminShell user={user} onUserChange={setUser} onLogout={async () => { await api.post("/logout"); setUser(null); }} />;
}
