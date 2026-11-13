import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import { messages } from "@/i18n/messages";

type Locale = keyof typeof messages;

interface I18nContextValue {
    locale: Locale;
    setLocale: (locale: Locale) => void;
    t: (key: string, replacements?: Record<string, string | number>) => string;
}

const I18nContext = createContext<I18nContextValue | null>(null);
const storageKey = "downloadportal.locale";

function messageFor(locale: Locale, key: string): string | undefined {
    return key.split(".").reduce<any>((message, part) => message?.[part], messages[locale]);
}

export function I18nProvider({ children }: { children: ReactNode }) {
    const storedLocale = localStorage.getItem(storageKey);
    const [locale, setLocale] = useState<Locale>(storedLocale === "en" ? "en" : "de");

    useEffect(() => {
        localStorage.setItem(storageKey, locale);
        document.documentElement.lang = locale;
    }, [locale]);

    const value = useMemo(() => ({
        locale,
        setLocale: (nextLocale: Locale) => setLocale(nextLocale),
        t: (key: string, replacements: Record<string, string | number> = {}) => {
            const message = messageFor(locale, key) ?? messageFor("de", key) ?? key;

            return Object.entries(replacements).reduce(
                (translated, [name, replacement]) => translated.replaceAll(`{{${name}}}`, String(replacement)),
                message,
            );
        },
    }), [locale]);

    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useTranslation() {
    const context = useContext(I18nContext);

    if (context === null) {
        throw new Error("useTranslation must be used within an I18nProvider.");
    }

    return context;
}
