import { createRoot } from "react-dom/client";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { AdminApp } from "@/AdminApp";
import { I18nProvider } from "@/i18n/I18nProvider";

createRoot(document.getElementById("app")!).render(
    <QueryClientProvider client={new QueryClient()}>
        <I18nProvider><AdminApp /></I18nProvider>
    </QueryClientProvider>,
);
