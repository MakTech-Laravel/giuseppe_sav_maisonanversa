import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/hooks/use-locale';
import { maisonUrl } from '@/lib/maison-navigation';

export type ProductCatalogFiltersState = {
    search: string;
    status: string;
    per_page?: number;
};

const DEFAULT_PER_PAGE = 12;

const fieldClassName =
    'w-full border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc outline-none transition-colors focus:border-gold2';

export function ProductCatalogFilters({
    filters,
}: {
    filters: ProductCatalogFiltersState;
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                maisonUrl('products', locale),
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    per_page:
                        filters.per_page && filters.per_page !== DEFAULT_PER_PAGE
                            ? filters.per_page
                            : undefined,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, status, filters.per_page]);

    return (
        <div className="mb-10 flex flex-col gap-4 sm:flex-row sm:items-center">
            <label className="sr-only" htmlFor="product-search">
                {t('Zoek producten')}
            </label>
            <input
                id="product-search"
                type="text"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={t('Zoek op naam of slug…')}
                className={`${fieldClassName} sm:max-w-80`}
            />

            <Select value={status} onValueChange={setStatus}>
                <SelectTrigger
                    className={`${fieldClassName} h-auto w-full justify-between rounded-none shadow-none sm:max-w-52`}
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{t('Alle statussen')}</SelectItem>
                    <SelectItem value="active">{t('Beschikbaar')}</SelectItem>
                    <SelectItem value="coming_soon">
                        {t('Binnenkort')}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    );
}
