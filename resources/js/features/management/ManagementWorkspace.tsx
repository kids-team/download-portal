import { useState } from "react";
import { ManagementPlaceholder } from "@/features/management/ManagementPlaceholder";
import { ManagementSidebar } from "@/features/management/ManagementSidebar";
import { TaxonomyManager } from "@/features/management/TaxonomyManager";
import { UserManager } from "@/features/management/UserManager";

export function ManagementWorkspace() {
    const [section, setSection] = useState("categories");

    return (
        <div className="grid min-h-[calc(100svh-57px)] grid-cols-1 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <ManagementSidebar activeSection={section} onSelect={setSection} />
            <main className="p-6">
                {section === "categories" && <TaxonomyManager endpoint="categories" translationKey="management.categories" />}
                {section === "audiences" && <TaxonomyManager endpoint="audiences" translationKey="management.audiences" />}
                {section === "tags" && <TaxonomyManager endpoint="tags" translationKey="management.tags" />}
                {section === "users" && <UserManager />}
                {section === "settings" && <ManagementPlaceholder translationKey="management.settings" />}
            </main>
        </div>
    );
}
