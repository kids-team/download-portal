import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useTranslation } from "@/i18n/I18nProvider";

const emptyUser = { name: "", email: "", role: "editor", password: "", password_confirmation: "" };

export function UserDialog({ user, open, onOpenChange, onSubmit, saving, error }: any) {
    const { t } = useTranslation();
    const [form, setForm] = useState(emptyUser);
    const isEditing = Boolean(user);

    useEffect(() => {
        if (!open) {
            return;
        }

        setForm(user
            ? { name: user.name, email: user.email, role: user.role, password: "", password_confirmation: "" }
            : emptyUser);
    }, [open, user]);

    const update = (changes: Record<string, string>) => setForm((current) => ({ ...current, ...changes }));

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{isEditing ? t("management.users.edit") : t("management.users.create")}</DialogTitle>
                    <DialogDescription>{isEditing ? t("management.users.editDescription") : t("management.users.createDescription")}</DialogDescription>
                </DialogHeader>
                <form className="grid gap-4" onSubmit={(event) => { event.preventDefault(); onSubmit(form); }}>
                    <div className="grid gap-2">
                        <Label htmlFor="user-name">{t("user.name")}</Label>
                        <Input id="user-name" value={form.name} onChange={(event) => update({ name: event.target.value })} required />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="user-email">{t("user.email")}</Label>
                        <Input id="user-email" type="email" value={form.email} onChange={(event) => update({ email: event.target.value })} required />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="user-role">{t("user.role")}</Label>
                        <select id="user-role" className="h-9 rounded-md border bg-white px-3 text-sm" value={form.role} onChange={(event) => update({ role: event.target.value })}>
                            <option value="editor">{t("user.editor")}</option>
                            <option value="admin">{t("user.administrator")}</option>
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="user-password">{isEditing ? t("user.newPassword") : t("user.password")}</Label>
                        <Input id="user-password" type="password" minLength={12} value={form.password} onChange={(event) => update({ password: event.target.value })} required={!isEditing} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="user-password-confirmation">{t("user.passwordConfirmation")}</Label>
                        <Input id="user-password-confirmation" type="password" minLength={12} value={form.password_confirmation} onChange={(event) => update({ password_confirmation: event.target.value })} required={!isEditing || form.password !== ""} />
                    </div>
                    {error && <p className="text-sm text-red-600">{t("management.users.saveError")}</p>}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>{t("actions.cancel")}</Button>
                        <Button type="submit" disabled={saving}>{isEditing ? t("actions.save") : t("management.users.create")}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
