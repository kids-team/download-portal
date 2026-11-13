import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Pencil, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { api, useApiQuery } from "@/lib/api";
import { UserDialog } from "@/features/management/UserDialog";
import { useTranslation } from "@/i18n/I18nProvider";

export function UserManager() {
    const { t } = useTranslation();
    const queryPath = "/api/v1/admin/users";
    const cache = useQueryClient();
    const users = useApiQuery(queryPath);
    const [selectedUser, setSelectedUser] = useState(undefined);
    const [dialogOpen, setDialogOpen] = useState(false);
    const items = Array.isArray(users.data) ? users.data : (users.data?.data ?? []);
    const refresh = () => cache.invalidateQueries({ queryKey: [queryPath] });
    const save = useMutation<any, any, any>({
        mutationFn: ({ user, attributes }) => user
            ? api.patch(`${queryPath}/${user.id}`, attributes)
            : api.post(queryPath, attributes),
        onSuccess: () => {
            setDialogOpen(false);
            setSelectedUser(undefined);
            refresh();
        },
    });
    const openCreateDialog = () => {
        setSelectedUser(undefined);
        setDialogOpen(true);
    };
    const openEditDialog = (user: any) => {
        setSelectedUser(user);
        setDialogOpen(true);
    };

    return (
        <section className="mx-auto grid w-full max-w-4xl gap-6">
            <header>
                <h1 className="text-2xl font-semibold">{t("management.users.title")}</h1>
                <p className="mt-1 text-sm text-slate-500">{t("management.users.description")}</p>
            </header>
            <div><Button type="button" onClick={openCreateDialog}><Plus /> {t("management.users.create")}</Button></div>
            <div className="overflow-hidden rounded-lg border bg-white">
                {users.isLoading && <p className="p-4 text-sm text-slate-500">{t("taxonomy.loading")}</p>}
                {users.isError && <p className="p-4 text-sm text-red-600">{t("management.users.loadError")}</p>}
                {items.map((user: any) => (
                    <div key={user.id} className="flex items-center gap-3 border-b px-4 py-3 last:border-b-0">
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{user.name}</p>
                            <p className="truncate text-xs text-slate-500">{user.email}</p>
                        </div>
                        <span className="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600">{user.role === "admin" ? t("user.administrator") : t("user.editor")}</span>
                        <Button type="button" variant="ghost" size="icon" onClick={() => openEditDialog(user)} aria-label={t("taxonomy.edit", { name: user.name })}><Pencil /></Button>
                    </div>
                ))}
            </div>
            <UserDialog
                user={selectedUser}
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                onSubmit={(attributes: any) => save.mutate({ user: selectedUser, attributes })}
                saving={save.isPending}
                error={save.isError}
            />
        </section>
    );
}
