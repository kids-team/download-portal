import { useEffect, useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { api } from "@/lib/api";
import { useTranslation } from "@/i18n/I18nProvider";

export function ProfileWorkspace({ user, onUserChange }: any) {
    const { t } = useTranslation();
    const [form, setForm] = useState({ name: user.name, email: user.email, locale: user.locale ?? "de", current_password: "", password: "", password_confirmation: "" });
    const update = (changes: Record<string, string>) => setForm((current) => ({ ...current, ...changes }));

    useEffect(() => {
        setForm({ name: user.name, email: user.email, locale: user.locale ?? "de", current_password: "", password: "", password_confirmation: "" });
    }, [user]);

    const save = useMutation({
        mutationFn: () => api.patch("/api/v1/admin/profile", form),
        onSuccess: (response) => {
            onUserChange(response.data.data);
            setForm((current) => ({ ...current, current_password: "", password: "", password_confirmation: "" }));
        },
    });

    return (
        <main className="p-6">
            <section className="mx-auto grid w-full max-w-2xl gap-6">
                <header>
                    <h1 className="text-2xl font-semibold">{t("profile.title")}</h1>
                    <p className="mt-1 text-sm text-slate-500">{t("profile.description")}</p>
                </header>
                <form className="grid gap-5 rounded-lg border bg-white p-5" onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
                    <div className="grid gap-2">
                        <Label htmlFor="profile-name">{t("user.name")}</Label>
                        <Input id="profile-name" value={form.name} onChange={(event) => update({ name: event.target.value })} required />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="profile-email">{t("user.email")}</Label>
                        <Input id="profile-email" type="email" value={form.email} onChange={(event) => update({ email: event.target.value })} required />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="profile-locale">{t("profile.language")}</Label>
                        <select id="profile-locale" className="h-9 rounded-md border bg-white px-3 text-sm" value={form.locale} onChange={(event) => update({ locale: event.target.value })}>
                            <option value="de">{t("language.de")}</option>
                            <option value="en">{t("language.en")}</option>
                        </select>
                    </div>
                    <fieldset className="grid gap-5 border-t pt-5">
                        <legend className="pr-2 text-sm font-medium">{t("profile.passwordSection")}</legend>
                        <div className="grid gap-2">
                            <Label htmlFor="profile-current-password">{t("profile.currentPassword")}</Label>
                            <Input id="profile-current-password" type="password" value={form.current_password} onChange={(event) => update({ current_password: event.target.value })} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="profile-password">{t("profile.newPassword")}</Label>
                            <Input id="profile-password" type="password" minLength={12} value={form.password} onChange={(event) => update({ password: event.target.value })} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="profile-password-confirmation">{t("user.passwordConfirmation")}</Label>
                            <Input id="profile-password-confirmation" type="password" minLength={12} value={form.password_confirmation} onChange={(event) => update({ password_confirmation: event.target.value })} />
                        </div>
                    </fieldset>
                    {save.isSuccess && <p className="text-sm text-green-700">{t("profile.saved")}</p>}
                    {save.isError && <p className="text-sm text-red-600">{t("profile.saveError")}</p>}
                    <div><Button type="submit" disabled={save.isPending}>{t("actions.save")}</Button></div>
                </form>
            </section>
        </main>
    );
}
