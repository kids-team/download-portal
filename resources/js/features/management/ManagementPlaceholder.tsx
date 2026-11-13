import { useTranslation } from "@/i18n/I18nProvider";

export function ManagementPlaceholder({ translationKey }: any) {
    const { t } = useTranslation();

    return (
        <section className="mx-auto w-full max-w-3xl">
            <h1 className="text-2xl font-semibold">{t(`${translationKey}.title`)}</h1>
            <p className="mt-1 text-sm text-slate-500">{t(`${translationKey}.description`)}</p>
            <div className="mt-6 rounded-lg border border-dashed bg-white p-6 text-sm text-slate-500">{t("placeholder")}</div>
        </section>
    );
}
