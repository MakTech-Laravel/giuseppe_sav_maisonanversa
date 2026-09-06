import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    MaisonModal,
    modalInputClassName,
    modalNoteClassName,
} from '@/components/maison/modals/maison-modal';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';

type EditionOption = {
    id: number;
    edition_number: number;
    label: string;
    sku: string;
    status: 'archive' | 'available' | 'reserved' | 'allocated';
    selectable: boolean;
};

type EditionsResponse = {
    data: EditionOption[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        range: {
            start: number;
            end: number;
            start_label: string;
            end_label: string;
        };
    };
    links: {
        next: string | null;
    };
};

type EditionPickerModalProps = {
    product: OrderProductContext;
    onClose: () => void;
    onSelect: (edition: {
        editionPieceId: number;
        editionNumber: number;
        editionLabel: string;
    }) => void;
};

function statusLabelKey(status: EditionOption['status']): string | null {
    switch (status) {
        case 'archive':
            return 'Niet te koop';
        case 'allocated':
            return 'Verkocht';
        case 'reserved':
            return 'Gereserveerd';
        default:
            return null;
    }
}

export function EditionPickerModal({
    product,
    onClose,
    onSelect,
}: EditionPickerModalProps) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const [items, setItems] = useState<EditionOption[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [rangeLabel, setRangeLabel] = useState<string | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const sentinel = useRef<HTMLDivElement | null>(null);
    const loadingRef = useRef(false);

    const productSlug = product.productSlug;

    useEffect(() => {
        const handle = window.setTimeout(() => {
            setSearch(searchInput.trim().toUpperCase());
        }, 250);

        return () => window.clearTimeout(handle);
    }, [searchInput]);

    const loadPage = useCallback(
        async (nextPage: number): Promise<void> => {
            if (!productSlug || loadingRef.current) {
                return;
            }

            loadingRef.current = true;
            setLoading(true);
            setError(null);

            try {
                const params = new URLSearchParams({
                    page: String(nextPage),
                });

                if (search !== '') {
                    params.set('search', search);
                }

                const response = await fetch(
                    `/${locale}/products/${productSlug}/editions?${params.toString()}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error('Failed to load editions');
                }

                const payload = (await response.json()) as EditionsResponse;

                setItems((current) =>
                    nextPage === 1
                        ? payload.data
                        : [...current, ...payload.data],
                );
                setPage(payload.meta.current_page);
                setLastPage(payload.meta.last_page);
                setRangeLabel(
                    `${payload.meta.range.start_label} – ${payload.meta.range.end_label}`,
                );
            } catch {
                setError(t('Editienummers konden niet worden geladen.'));
            } finally {
                loadingRef.current = false;
                setLoading(false);
            }
        },
        [locale, productSlug, search, t],
    );

    // Fetching data on a dependency change (locale/productSlug/search) is a
    // legitimate effect — see
    // https://react.dev/learn/you-might-not-need-an-effect#fetching-data.
    // The resets below just clear stale results before the new page loads.
    useEffect(() => {
        // eslint-disable-next-line react-hooks/set-state-in-effect -- data fetch triggered by a dependency change, not derived state
        setItems([]);
        setPage(1);
        setLastPage(1);
        void loadPage(1);
    }, [loadPage]);

    useEffect(() => {
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
            { rootMargin: '120px' },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [lastPage, loadPage, page]);

    return (
        <MaisonModal
            label={t('Kies uw editienummer')}
            onClose={onClose}
            panelClassName="max-w-[640px]"
        >
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {t('Kies uw editienummer')}
            </h2>
            <span className="mb-1 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {product.productName}
            </span>
            {rangeLabel && (
                <p className="mb-3 font-sans text-[11px] tracking-[0.14em] text-stone uppercase">
                    {t('Editierange')}: {rangeLabel}
                </p>
            )}
            <p className={cn(modalNoteClassName, 'mb-3')}>
                {t(
                    'Beschikbare nummers kunt u selecteren. Niet te koop, gereserveerd of verkocht zijn niet selecteerbaar.',
                )}
            </p>

            <label className="sr-only" htmlFor="edition-search">
                {t('Zoek op editienummer')}
            </label>
            <input
                id="edition-search"
                type="search"
                value={searchInput}
                onChange={(event) => setSearchInput(event.target.value)}
                placeholder={t('Zoek op editienummer')}
                className={cn(modalInputClassName, 'mb-3')}
            />

            <div className="max-h-[360px] overflow-y-auto border border-gold/20">
                {items.length === 0 && !loading ? (
                    <p className="px-4 py-8 text-center text-[13px] text-choc3">
                        {error ??
                            (search !== ''
                                ? t(
                                      'Geen editie gevonden voor deze zoekopdracht.',
                                  )
                                : t('Geen editienummers gevonden.'))}
                    </p>
                ) : (
                    <ul className="divide-y divide-gold/15">
                        {items.map((item) => {
                            const badge = statusLabelKey(item.status);

                            if (!item.selectable) {
                                return (
                                    <li
                                        key={item.id}
                                        className="flex w-full items-center justify-between gap-3 px-4 py-3 opacity-55"
                                    >
                                        <span className="font-serif text-[18px] text-choc">
                                            {item.label}
                                        </span>
                                        <span className="font-sans text-[10px] tracking-[0.14em] text-stone uppercase">
                                            {badge ? t(badge) : item.sku}
                                        </span>
                                    </li>
                                );
                            }

                            return (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors hover:bg-gold/10"
                                        onClick={() =>
                                            onSelect({
                                                editionPieceId: item.id,
                                                editionNumber:
                                                    item.edition_number,
                                                editionLabel: item.label,
                                            })
                                        }
                                    >
                                        <span className="font-serif text-[18px] text-choc">
                                            {item.label}
                                        </span>
                                        <span className="font-sans text-[10px] tracking-[0.14em] text-stone uppercase">
                                            {item.sku}
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
                <div ref={sentinel} className="h-8" />
                {loading && (
                    <p className="px-4 py-3 text-center font-sans text-[10px] tracking-[0.16em] text-stone uppercase">
                        {t('Laden…')}
                    </p>
                )}
            </div>

            {error && items.length > 0 && (
                <p role="alert" className="mt-3 text-[13px] text-choc3">
                    {error}
                </p>
            )}

            <MaisonButton
                type="button"
                variant="outlineChoc"
                block
                className="mt-4"
                onClick={onClose}
            >
                {t('Annuleren')}
            </MaisonButton>
        </MaisonModal>
    );
}
