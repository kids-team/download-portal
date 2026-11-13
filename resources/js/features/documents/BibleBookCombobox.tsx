import { useTranslation } from "@/i18n/I18nProvider";
import { useId, useRef, useState, type KeyboardEvent } from "react";
import { Check, ChevronsUpDown } from "lucide-react";
import { Input } from "@/components/ui/input";
import type { BibleBook } from "./bibleReference";

interface BibleBookComboboxProps {
    id: string;
    books: BibleBook[];
    value: string;
    onChange: (slug: string) => void;
    disabled?: boolean;
}

export function BibleBookCombobox({ id, books, value, onChange, disabled }: BibleBookComboboxProps) {
    const { t } = useTranslation();
    const listId = useId();
    const input = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState("");
    const [activeIndex, setActiveIndex] = useState(-1);
    const selectedBook = books.find((book) => book.short_name === value);
    const normalizedSearch = search.trim().toLocaleLowerCase("de");
    const matches = books.filter((book) =>
        `${book.long_name} ${book.short_name}`.toLocaleLowerCase("de").includes(normalizedSearch),
    );

    const selectBook = (book: BibleBook) => {
        onChange(book.short_name);
        setOpen(false);
        setSearch("");
        setActiveIndex(-1);
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            setOpen(true);
            const nextIndex = matches.length === 0 ? -1 : event.key === "ArrowDown"
                ? Math.min(activeIndex + 1, matches.length - 1)
                : activeIndex < 0 ? matches.length - 1 : Math.max(activeIndex - 1, 0);
            setActiveIndex(nextIndex);
            document.getElementById(`${listId}-${nextIndex}`)?.scrollIntoView({ block: "nearest" });
        } else if (event.key === "Enter" && open) {
            event.preventDefault();
            const book = matches[activeIndex];
            if (book) {
                selectBook(book);
            }
        } else if (event.key === "Escape" && open) {
            event.preventDefault();
            event.stopPropagation();
            setOpen(false);
            setSearch("");
            setActiveIndex(-1);
        }
    };

    return (
        <div className="relative">
            <Input
                ref={input}
                id={id}
                role="combobox"
                aria-autocomplete="list"
                aria-expanded={open}
                aria-controls={listId}
                aria-activedescendant={open && activeIndex >= 0 ? `${listId}-${activeIndex}` : undefined}
                autoComplete="off"
                placeholder={selectedBook?.long_name ?? t("bible.searchBook")}
                className="pr-9"
                value={open ? search : (selectedBook?.long_name ?? "")}
                disabled={disabled}
                onFocus={() => { setOpen(true); setActiveIndex(-1); }}
                onClick={() => setOpen(true)}
                onBlur={() => { setOpen(false); setSearch(""); setActiveIndex(-1); }}
                onChange={(event) => { setSearch(event.target.value); setActiveIndex(-1); setOpen(true); }}
                onKeyDown={handleKeyDown}
            />
            <button
                type="button"
                className="absolute inset-y-0 right-0 grid w-9 place-items-center text-muted-foreground disabled:opacity-50"
                aria-label={t("bible.openBooks")}
                tabIndex={-1}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => {
                    input.current?.focus();
                    setOpen(!open);
                    setSearch("");
                    setActiveIndex(-1);
                }}
            >
                <ChevronsUpDown className="size-4" />
            </button>
            {open && (
                <ul id={listId} role="listbox" aria-label={t("bible.books")} className="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-popover p-1 text-popover-foreground shadow-md">
                    {matches.map((book, index) => (
                        <li
                            key={book.short_name}
                            id={`${listId}-${index}`}
                            role="option"
                            aria-selected={book.short_name === value}
                            className={`flex cursor-pointer items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm ${index === activeIndex ? "bg-accent text-accent-foreground" : ""}`}
                            onMouseDown={(event) => event.preventDefault()}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => selectBook(book)}
                        >
                            {book.long_name}
                            {book.short_name === value && <Check className="size-4" />}
                        </li>
                    ))}
                    {matches.length === 0 && <li role="presentation" className="px-2 py-3 text-sm text-muted-foreground">{t("bible.noBooks")}</li>}
                </ul>
            )}
        </div>
    );
}
