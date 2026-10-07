import { Check, Loader2, Search, UserRound } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { search as customersSearch } from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

export type PickerCustomer = {
    id: number;
    name: string;
    email: string;
};

type CustomersSearchResponse = {
    data: PickerCustomer[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: {
        next: string | null;
    };
};

type AdminCustomerPickerSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    description?: string;
    selectedId?: number | null;
    onSelect: (customer: PickerCustomer) => void;
};

export function AdminCustomerPickerSheet({
    open,
    onOpenChange,
    title,
    description,
    selectedId = null,
    onSelect,
}: AdminCustomerPickerSheetProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [items, setItems] = useState<PickerCustomer[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const sentinel = useRef<HTMLDivElement | null>(null);
    const listRef = useRef<HTMLDivElement | null>(null);
    const loadingRef = useRef(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handle = window.setTimeout(() => {
            setSearch(searchInput.trim());
        }, 250);

        return () => window.clearTimeout(handle);
    }, [open, searchInput]);

    useEffect(() => {
        if (!open) {
            setSearchInput('');
            setSearch('');
            setItems([]);
            setPage(1);
            setLastPage(1);
            setError(null);
        }
    }, [open]);

    const loadPage = useCallback(
        async (nextPage: number): Promise<void> => {
            if (loadingRef.current) {
                return;
            }

            loadingRef.current = true;
            setLoading(true);
            setError(null);

            try {
                const response = await fetch(
                    customersSearch.url(
                        { locale },
                        {
                            query: {
                                page: nextPage,
                                ...(search !== '' ? { search } : {}),
                            },
                        },
                    ),
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error('Failed to load customers');
                }

                const payload = (await response.json()) as CustomersSearchResponse;

                setItems((current) =>
                    nextPage === 1
                        ? payload.data
                        : [...current, ...payload.data],
                );
                setPage(payload.meta.current_page);
                setLastPage(payload.meta.last_page);
            } catch {
                setError(t('Klanten konden niet worden geladen.'));
            } finally {
                loadingRef.current = false;
                setLoading(false);
            }
        },
        [locale, search, t],
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        // eslint-disable-next-line react-hooks/set-state-in-effect -- reset before refetch when search/open changes
        setItems([]);
        setPage(1);
        setLastPage(1);
        void loadPage(1);
    }, [loadPage, open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const node = sentinel.current;

        if (!node) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (
                    entries[0]?.isIntersecting &&
                    !loadingRef.current &&
                    page < lastPage
                ) {
                    void loadPage(page + 1);
                }
            },
            {
                root: listRef.current,
                rootMargin: '120px',
            },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [lastPage, loadPage, open, page]);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-md"
            >
                <SheetHeader className="border-b px-5 py-4 pr-12">
                    <SheetTitle>
                        {title ?? t('Klant selecteren')}
                    </SheetTitle>
                    <SheetDescription>
                        {description ??
                            t(
                                'Zoek op naam of e-mail en kies een klantaccount.',
                            )}
                    </SheetDescription>
                </SheetHeader>

                <div className="border-b px-5 py-3">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={searchInput}
                            onChange={(event) =>
                                setSearchInput(event.target.value)
                            }
                            placeholder={t('Zoek op naam of e-mail')}
                            className="pl-9"
                            autoFocus
                        />
                    </div>
                </div>

                <div
                    ref={listRef}
                    className="min-h-0 flex-1 overflow-y-auto px-2 py-2"
                >
                    {items.length === 0 && !loading ? (
                        <p className="px-3 py-8 text-center text-sm text-muted-foreground">
                            {error ?? t('Geen klanten gevonden.')}
                        </p>
                    ) : (
                        <ul className="space-y-1">
                            {items.map((customer) => {
                                const selected = selectedId === customer.id;

                                return (
                                    <li key={customer.id}>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                onSelect(customer);
                                                onOpenChange(false);
                                            }}
                                            className={cn(
                                                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors hover:bg-muted/60',
                                                selected && 'bg-muted',
                                            )}
                                        >
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-full border bg-background">
                                                <UserRound className="size-4 text-muted-foreground" />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-medium">
                                                    {customer.name}
                                                </span>
                                                <span className="block truncate text-xs text-muted-foreground">
                                                    {customer.email}
                                                </span>
                                            </span>
                                            {selected && (
                                                <Check className="size-4 shrink-0 text-foreground" />
                                            )}
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    )}

                    <div ref={sentinel} className="h-8" />

                    {loading && (
                        <div className="flex items-center justify-center gap-2 py-4 text-sm text-muted-foreground">
                            <Loader2 className="size-4 animate-spin" />
                            {t('Laden…')}
                        </div>
                    )}

                    {error && items.length > 0 && (
                        <p className="px-3 py-2 text-center text-sm text-destructive">
                            {error}
                        </p>
                    )}
                </div>

                <div className="border-t px-5 py-3">
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full"
                        onClick={() => onOpenChange(false)}
                    >
                        {t('Annuleren')}
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
