import { Head, Link, router, useForm } from '@inertiajs/react';
import { Loader2, Package, Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DataPagination } from '@/components/admin/data-pagination';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';
import heritageRoutes from '@/routes/admin/heritage';
import orders from '@/routes/admin/orders';
import type { Paginated } from '@/types/admin';

interface HeritageProduct {
    id: number;
    name: string;
    slug?: string;
    amount: string;
    currency: string;
    stripe_price_id: string | null;
    edition_total?: number | null;
    edition_number_prefix?: string | null;
    edition_number_postfix?: string | null;
}

interface CatalogOption {
    id: number;
    name: string;
}

interface HeritageInventory {
    product_name: string;
    total: number;
    reserved: number;
    available: number;
}

interface InventoryRow {
    sku: string;
    label: string;
    status: string;
    status_key: string;
    notes: string;
    order_id?: number | null;
    order_reference?: string | null;
    purchaser_user_id?: number | null;
    purchaser_name?: string | null;
    purchaser_email?: string | null;
}

interface InventoryFilters {
    search: string;
    number_from: string;
    number_to: string;
    status: string;
    per_page: number;
}

const STATUS_OPTIONS = [
    'archive',
    'available',
    'reserved',
    'allocated',
] as const;

const DEFAULT_PER_PAGE = 75;

function translateInventoryStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        archive: 'Archief',
        available: 'Beschikbaar',
        reserved: 'Gereserveerd',
        allocated: 'Toegewezen',
    };

    return t(statusMap[status] ?? status);
}

function inventoryQuery(
    productId: number | undefined,
    filters: InventoryFilters,
): Record<string, string | number | undefined> {
    return {
        product: productId,
        search: filters.search || undefined,
        number_from: filters.number_from || undefined,
        number_to: filters.number_to || undefined,
        status: filters.status || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function HeritageIndex({
    product,
    catalog = [],
    inventory,
    pieces,
    filters,
    perPageOptions = [25, 50, 75, 100, 150, 200, 300],
}: {
    product: HeritageProduct | null;
    catalog?: CatalogOption[];
    inventory: HeritageInventory;
    pieces: Paginated<InventoryRow>;
    filters: InventoryFilters;
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [numberFrom, setNumberFrom] = useState(filters.number_from ?? '');
    const [numberTo, setNumberTo] = useState(filters.number_to ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [perPage, setPerPage] = useState(
        filters.per_page ?? DEFAULT_PER_PAGE,
    );
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                heritageRoutes.index(wayfinderLocale()).url,
                inventoryQuery(product?.id, {
                    search,
                    number_from: numberFrom,
                    number_to: numberTo,
                    status: status === 'all' ? '' : status,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, numberFrom, numberTo, status, perPage, product?.id]);

    const hasActiveFilters =
        Boolean(search) ||
        Boolean(numberFrom) ||
        Boolean(numberTo) ||
        status !== 'all';

    const clearFilters = () => {
        setSearch('');
        setNumberFrom('');
        setNumberTo('');
        setStatus('all');
    };

    return (
        <>
            <Head title={t('Editievoorraad')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Editievoorraad')}
                    description={t(
                        'Nummering en voorraad per limited-edition product.',
                    )}
                    icon={Package}
                />
                {catalog.length > 1 && product && (
                    <div className="grid max-w-md gap-2">
                        <Label htmlFor="catalog-product">{t('Product')}</Label>
                        <Select
                            value={String(product.id)}
                            onValueChange={(value) => {
                                router.get(
                                    heritageRoutes.index(wayfinderLocale()).url,
                                    { product: value },
                                    { preserveState: false },
                                );
                            }}
                        >
                            <SelectTrigger
                                id="catalog-product"
                                className="w-full"
                            >
                                <SelectValue placeholder={t('Product')} />
                            </SelectTrigger>
                            <SelectContent>
                                {catalog.map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={String(item.id)}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {product && <HeritageProductForm product={product} />}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label={t('Product')}
                        value={inventory.product_name}
                        emphasize
                    />
                    <StatCard
                        label={t('Totaal')}
                        value={String(inventory.total)}
                    />
                    <StatCard
                        label={t('Gereserveerd')}
                        value={String(inventory.reserved)}
                    />
                    <StatCard
                        label={t('Beschikbaar')}
                        value={String(inventory.available)}
                    />
                </div>

                <div className="rounded-xl border bg-card p-4 shadow-sm sm:p-5">
                    <div className="grid gap-3 lg:grid-cols-[minmax(0,1.4fr)_repeat(3,minmax(0,0.7fr))_minmax(0,0.65fr)_auto]">
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={t(
                                    'Zoek op SKU, nummer of notities…',
                                )}
                                className="pl-9"
                                aria-label={t('Zoeken')}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="number-from" className="sr-only">
                                {t('Nummer van')}
                            </Label>
                            <Input
                                id="number-from"
                                type="number"
                                min={1}
                                inputMode="numeric"
                                value={numberFrom}
                                onChange={(event) =>
                                    setNumberFrom(event.target.value)
                                }
                                placeholder={t('Van #')}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="number-to" className="sr-only">
                                {t('Nummer tot')}
                            </Label>
                            <Input
                                id="number-to"
                                type="number"
                                min={1}
                                inputMode="numeric"
                                value={numberTo}
                                onChange={(event) =>
                                    setNumberTo(event.target.value)
                                }
                                placeholder={t('Tot #')}
                            />
                        </div>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger
                                className="w-full"
                                aria-label={t('Status')}
                            >
                                <SelectValue
                                    placeholder={t('Alle statussen')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('Alle statussen')}
                                </SelectItem>
                                {STATUS_OPTIONS.map((value) => (
                                    <SelectItem key={value} value={value}>
                                        {translateInventoryStatus(value, t)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={String(perPage)}
                            onValueChange={(value) => setPerPage(Number(value))}
                        >
                            <SelectTrigger
                                className="w-full"
                                aria-label={t('Per pagina')}
                            >
                                <SelectValue placeholder={t('Per pagina')} />
                            </SelectTrigger>
                            <SelectContent>
                                {perPageOptions.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {t('{{count}} per pagina', {
                                            count: option,
                                        })}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {hasActiveFilters && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={clearFilters}
                                className="w-full lg:w-auto"
                            >
                                <X className="h-4 w-4" />
                                {t('Filters wissen')}
                            </Button>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="h-11 px-4">
                                    {t('SKU')}
                                </TableHead>
                                <TableHead className="h-11 px-4">
                                    {t('Label')}
                                </TableHead>
                                <TableHead className="h-11 px-4">
                                    {t('Status')}
                                </TableHead>
                                <TableHead className="hidden h-11 px-4 lg:table-cell">
                                    {t('Koper')}
                                </TableHead>
                                <TableHead className="hidden h-11 px-4 md:table-cell">
                                    {t('Bestelling')}
                                </TableHead>
                                <TableHead className="hidden h-11 px-4 md:table-cell">
                                    {t('Notities')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pieces.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="px-4 py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen editienummers gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                pieces.data.map((row) => (
                                    <TableRow key={row.sku}>
                                        <TableCell className="px-4 py-3 align-middle font-mono text-sm font-medium">
                                            {row.sku}
                                        </TableCell>
                                        <TableCell className="px-4 py-3 align-middle font-mono text-sm">
                                            {row.label}
                                        </TableCell>
                                        <TableCell className="px-4 py-3 align-middle">
                                            <Badge variant="secondary">
                                                {translateInventoryStatus(
                                                    row.status,
                                                    t,
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden px-4 py-3 align-middle text-sm lg:table-cell">
                                            {row.purchaser_name ? (
                                                row.purchaser_user_id ? (
                                                    <Link
                                                        href={customers.show({
                                                            locale,
                                                            user: row.purchaser_user_id,
                                                        })}
                                                        className="font-medium text-foreground underline-offset-2 hover:underline"
                                                    >
                                                        {row.purchaser_name}
                                                    </Link>
                                                ) : (
                                                    row.purchaser_name
                                                )
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden px-4 py-3 align-middle text-sm md:table-cell">
                                            {row.order_id ? (
                                                <Link
                                                    href={orders.show({
                                                        locale,
                                                        order: row.order_id,
                                                    })}
                                                    className="font-medium text-foreground underline-offset-2 hover:underline"
                                                >
                                                    {row.order_reference ??
                                                        row.order_id}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden px-4 py-3 align-middle text-muted-foreground md:table-cell">
                                            {row.notes || '—'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                    <DataPagination meta={pieces} />
                </div>
            </div>
        </>
    );
}

function HeritageProductForm({ product }: { product: HeritageProduct }) {
    const { t } = useTranslation();
    const form = useForm(
        heritageRoutes.update({
            locale: wayfinderLocale(),
            product: product.id,
        }),
        {
            name: product.name,
            amount: product.amount,
            edition_number_prefix: product.edition_number_prefix ?? '',
            edition_number_postfix: product.edition_number_postfix ?? '',
        },
    );

    const padWidth = Math.max(
        3,
        String(Math.max(product.edition_total ?? 1, 1)).length,
    );
    const digits = String(1).padStart(padWidth, '0');
    const hasAffix =
        form.data.edition_number_prefix !== '' ||
        form.data.edition_number_postfix !== '';
    const fallbackPrefix =
        (product.slug ?? 'pr')
            .replace(/[^a-zA-Z]/g, '')
            .toUpperCase()
            .slice(0, 2) || 'PR';
    const previewSku = hasAffix
        ? `${form.data.edition_number_prefix}${digits}${form.data.edition_number_postfix}`
        : `${fallbackPrefix}-${digits}`;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
    };

    return (
        <form
            onSubmit={submit}
            className="space-y-5 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
        >
            <div className="grid items-start gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">{t('Naam')}</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="amount">{t('Bedrag')}</Label>
                    <div className="relative">
                        <Input
                            id="amount"
                            name="amount"
                            type="text"
                            inputMode="decimal"
                            value={form.data.amount}
                            onChange={(event) =>
                                form.setData('amount', event.target.value)
                            }
                            className="pr-12"
                        />
                        <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                            EUR
                        </span>
                    </div>
                    <InputError message={form.errors.amount} />
                </div>
            </div>

            <div className="border-t pt-5">
                <div className="mb-4">
                    <h3 className="text-sm font-semibold tracking-tight">
                        {t('SKU-opbouw')}
                    </h3>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {t(
                            'SKU’s worden opgebouwd als prefix + editienummer + postfix. Sla op om de tabel bij te werken.',
                        )}
                    </p>
                </div>
                <div className="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="edition_number_prefix">
                            {t('Prefix')}
                        </Label>
                        <Input
                            id="edition_number_prefix"
                            value={form.data.edition_number_prefix}
                            onChange={(event) =>
                                form.setData(
                                    'edition_number_prefix',
                                    event.target.value,
                                )
                            }
                            placeholder={t('bijv. No. of MA-')}
                        />
                        <InputError
                            message={form.errors.edition_number_prefix}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="edition_number_postfix">
                            {t('Postfix')}
                        </Label>
                        <Input
                            id="edition_number_postfix"
                            value={form.data.edition_number_postfix}
                            onChange={(event) =>
                                form.setData(
                                    'edition_number_postfix',
                                    event.target.value,
                                )
                            }
                            placeholder={t('bijv. -A of /100')}
                        />
                        <InputError
                            message={form.errors.edition_number_postfix}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label>{t('Voorbeeld')}</Label>
                        <div className="flex h-9 items-center rounded-md border bg-muted/30 px-3 font-mono text-sm">
                            {previewSku}
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex justify-end border-t pt-5">
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="w-full sm:w-auto"
                >
                    {form.processing && (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    )}
                    {t('Wijzigingen opslaan')}
                </Button>
            </div>
        </form>
    );
}

function StatCard({
    label,
    value,
    emphasize = false,
}: {
    label: string;
    value: string;
    emphasize?: boolean;
}) {
    return (
        <div className="flex min-h-[6.5rem] flex-col justify-between rounded-xl border bg-card p-5 shadow-sm">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p
                className={cn(
                    'mt-3 leading-snug',
                    emphasize
                        ? 'text-sm font-medium'
                        : 'font-serif text-2xl font-medium tabular-nums',
                )}
            >
                {value}
            </p>
        </div>
    );
}

HeritageIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Editievoorraad',
            href: heritageRoutes.index(wayfinderLocale()),
        },
    ],
};
