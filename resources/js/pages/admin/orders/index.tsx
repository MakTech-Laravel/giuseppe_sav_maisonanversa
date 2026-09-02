import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search, ShoppingBag, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DataPagination } from '@/components/admin/data-pagination';
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
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';
import ordersRoutes from '@/routes/admin/orders';
import type { Paginated } from '@/types/admin';

interface OrderRow {
    id: string;
    reference?: string;
    product_name: string;
    label: string;
    customer: string;
    customer_email: string;
    user_id: number | null;
    date: string;
    amount: string;
    status: string;
    status_key: string;
    payment_status: string | null;
    payment_status_key: string | null;
}

interface OrderFilters {
    search: string;
    status: string;
    payment_status: string;
    per_page: number;
}

interface StatusOption {
    value: string;
    label: string;
}

const DEFAULT_PER_PAGE = 15;

function ordersQuery(
    filters: OrderFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
        payment_status: filters.payment_status || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function OrdersIndex({
    orders: paginated,
    filters,
    statusOptions = [],
    paymentStatusOptions = [],
    perPageOptions = [10, 15, 25, 50, 100],
}: {
    orders: Paginated<OrderRow>;
    filters: OrderFilters;
    statusOptions?: StatusOption[];
    paymentStatusOptions?: StatusOption[];
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [paymentStatus, setPaymentStatus] = useState(
        filters.payment_status || 'all',
    );
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
                ordersRoutes.index(locale).url,
                ordersQuery({
                    search,
                    status: status === 'all' ? '' : status,
                    payment_status:
                        paymentStatus === 'all' ? '' : paymentStatus,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, paymentStatus, perPage, locale]);

    const hasActiveFilters =
        Boolean(search) || status !== 'all' || paymentStatus !== 'all';

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
        setPaymentStatus('all');
    };

    const serialStart = paginated.from ?? 1;

    return (
        <>
            <Head title={t('Bestellingen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Bestellingen')}
                    description={t(
                        'Bekijk reserveringen, aanbetalingen en aankopen.',
                    )}
                    icon={ShoppingBag}
                />

                <div className="flex flex-wrap items-end justify-start gap-3">
                    <div className="w-full max-w-xs space-y-1.5 sm:w-64">
                        <Label htmlFor="orders-search">{t('Zoeken')}</Label>
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="orders-search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={t(
                                    'Zoek op klant, e-mail, product of ID…',
                                )}
                                className="pl-9"
                            />
                        </div>
                    </div>

                    <div className="w-full max-w-[11rem] space-y-1.5 sm:w-44">
                        <Label htmlFor="orders-status">
                            {t('Bestelstatus')}
                        </Label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger id="orders-status">
                                <SelectValue
                                    placeholder={t('Alle statussen')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('Alle statussen')}
                                </SelectItem>
                                {statusOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="w-full max-w-[11rem] space-y-1.5 sm:w-44">
                        <Label htmlFor="orders-payment-status">
                            {t('Betalingsstatus')}
                        </Label>
                        <Select
                            value={paymentStatus}
                            onValueChange={setPaymentStatus}
                        >
                            <SelectTrigger id="orders-payment-status">
                                <SelectValue
                                    placeholder={t('Alle betalingsstatussen')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('Alle betalingsstatussen')}
                                </SelectItem>
                                {paymentStatusOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="w-full max-w-[7rem] space-y-1.5 sm:w-28">
                        <Label htmlFor="orders-per-page">
                            {t('Per pagina')}
                        </Label>
                        <Select
                            value={String(perPage)}
                            onValueChange={(value) => setPerPage(Number(value))}
                        >
                            <SelectTrigger id="orders-per-page">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {perPageOptions.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {hasActiveFilters && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={clearFilters}
                        >
                            <X className="h-4 w-4" />
                            {t('Filters wissen')}
                        </Button>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead className="w-14">
                                        {t('Nr.')}
                                    </TableHead>
                                    <TableHead>{t('Referentie')}</TableHead>
                                    <TableHead>{t('Product')}</TableHead>
                                    <TableHead>{t('Klant')}</TableHead>
                                    <TableHead className="hidden md:table-cell">
                                        {t('Datum')}
                                    </TableHead>
                                    <TableHead>{t('Bedrag')}</TableHead>
                                    <TableHead>{t('Bestelstatus')}</TableHead>
                                    <TableHead>
                                        {t('Betalingsstatus')}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t('Acties')}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginated.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={9}
                                            className="px-4 py-10 text-center text-sm text-muted-foreground"
                                        >
                                            {t('Geen bestellingen gevonden.')}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    paginated.data.map((order, index) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="text-muted-foreground">
                                                {serialStart + index}
                                            </TableCell>
                                            <TableCell>
                                                <Link
                                                    href={ordersRoutes.show({
                                                        locale,
                                                        order: Number(order.id),
                                                    })}
                                                    className="font-medium text-foreground underline-offset-2 hover:underline"
                                                >
                                                    {order.reference ??
                                                        order.id}
                                                </Link>
                                                {order.label !==
                                                    order.product_name && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {order.label}
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell className="max-w-[200px]">
                                                <span className="line-clamp-2 font-medium">
                                                    {order.product_name}
                                                </span>
                                            </TableCell>
                                            <TableCell className="max-w-[220px]">
                                                {order.user_id ? (
                                                    <Link
                                                        href={customers.show({
                                                            locale,
                                                            user: order.user_id,
                                                        })}
                                                        className="font-medium text-foreground underline-offset-2 hover:underline"
                                                    >
                                                        {order.customer}
                                                    </Link>
                                                ) : (
                                                    <span className="font-medium">
                                                        {order.customer}
                                                    </span>
                                                )}
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {order.customer_email}
                                                </p>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                {order.date}
                                            </TableCell>
                                            <TableCell>
                                                {order.amount}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {order.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {order.payment_status ? (
                                                    <Badge variant="outline">
                                                        {order.payment_status}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ordersRoutes.show(
                                                            {
                                                                locale,
                                                                order: Number(
                                                                    order.id,
                                                                ),
                                                            },
                                                        )}
                                                        title={t(
                                                            'Bestelling bekijken',
                                                        )}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(wayfinderLocale()),
        },
        {
            title: 'Bestellingen',
            href: ordersRoutes.index(wayfinderLocale()),
        },
    ],
};
