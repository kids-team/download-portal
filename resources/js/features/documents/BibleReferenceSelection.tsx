import { createContext, useContext } from "react";
import type { BibleReference } from "./bibleReference";

export interface SelectedBibleReference {
    id: symbol;
    reference: BibleReference;
    update: (reference: BibleReference) => void;
}

export const BibleReferenceSelectionContext = createContext<{
    select: (selection: SelectedBibleReference) => void;
    clear: (id: symbol) => void;
} | null>(null);

export function useBibleReferenceSelection() {
    return useContext(BibleReferenceSelectionContext);
}
