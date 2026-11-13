import { useTranslation } from "@/i18n/I18nProvider";
import { useState } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export function LoginForm({ onLogin }: any) {
    const { t } = useTranslation();
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [error, setError] = useState("");

    const submit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setError("");

        try {
            await api.get("/sanctum/csrf-cookie");
            const { data } = await api.post("/login", { email, password });
            onLogin(data.data);
        } catch (exception: any) {
            setError(
                exception.response?.data?.message ?? t("auth.failed"),
            );
        }
    };

    return (
        <main className="grid min-h-svh place-items-center p-6">
            <Card className="w-full max-w-md">
                <CardHeader>
                    <CardTitle>{t("auth.title")}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="email">{t("user.email")}</Label>
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(event) => setEmail(event.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password">{t("user.password")}</Label>
                            <Input
                                id="password"
                                type="password"
                                value={password}
                                onChange={(event) => setPassword(event.target.value)}
                                required
                            />
                        </div>
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        <Button type="submit">{t("auth.signIn")}</Button>
                    </form>
                </CardContent>
            </Card>
        </main>
    );
}
