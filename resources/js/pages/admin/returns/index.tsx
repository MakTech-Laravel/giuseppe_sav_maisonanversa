import { Head, Link, router } from '@inertiajs/react';
import { Package, Truck } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DataPagination } from '@/components/admin/data-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useLocale } from '@/hooks/use-locale';
import type { Paginated } from '@/types/admin';

type ReturnRow = {
    id: number;
    number: string;
    status_label: string;
    customer: string | null;
    order_reference: string | null;
    items: string[];
    requested_at: string | null;
    refund_due_by: string | null;
    due_soon: boolean;
    maison_pays_return_shipping: boolean;
};

export default function AdminReturnsIndex({
    returns: paginated,
    filters,
    statuses,
}: {
    returns: Paginated<ReturnRow>;
    filters: { status: string; search: string };
    statuses: { value: string; label: string }[];
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status || 'all');

    function apply() {
        router.get(
            `/${locale}/admin/returns`,
            {
                search: search || undefined,
                status: status === 'all' ? undefined : status,
            },
            { preserveState: true },
        );
    }

    return (
        <>
            <Head title={t('Retours')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Retours')}
                    description={t('Retouraanvragen van klanten')}
                    icon={Package}
                />

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={t('Zoeken')}
                        className="w-full sm:max-w-xs"
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                apply();
                            }
                        }}
                    />
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-full sm:w-48">
                            <SelectValue placeholder={t('Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t('Alle')}</SelectItem>
                            {statuses.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="button" onClick={apply}>
                        {t('Filter')}
                    </Button>
                </div>

                <div className="rounded-xl border bg-card shadow-sm">
                    <Table className="table-fixed">
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="w-[11%]">
                                    {t('Nummer')}
                                </TableHead>
                                <TableHead className="w-[12%]">
                                    {t('Bestelling')}
                                </TableHead>
                                <TableHead className="w-[14%]">
                                    {t('Klant')}
                                </TableHead>
                                <TableHead className="w-[22%]">
                                    {t('Artikelen')}
                                </TableHead>
                                <TableHead className="w-[16%]">
                                    {t('Status')}
                                </TableHead>
                                <TableHead className="w-[13%]">
                                    {t('Aangevraagd')}
                                </TableHead>
                                <TableHead className="w-[12%]">
                                    {t('Terugbetaling vóór')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="px-4 py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen retours gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-1.5">
                                                <Link
                                                    href={`/${locale}/admin/returns/${row.id}`}
                                                    className="font-medium text-foreground underline-offset-2 hover:underline"
                                                >
                                                    {row.number}
                                                </Link>
                                                {row.maison_pays_return_shipping ? (
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span className="inline-flex text-muted-foreground">
                                                                <Truck
                                                                    className="size-3.5 shrink-0"
                                                                    aria-label={t(
                                                                        'Onze verzending',
                                                                    )}
                                                                />
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            {t(
                                                                'Onze verzending',
                                                            )}
                                                        </TooltipContent>
                                                    </Tooltip>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {row.order_reference ?? '—'}
                                        </TableCell>
                                        <TableCell className="max-w-0 whitespace-normal">
                                            <span className="line-clamp-2 break-words">
                                                {row.customer ?? '—'}
                                            </span>
                                        </TableCell>
                                        <TableCell className="max-w-0 whitespace-normal">
                                            <span className="line-clamp-2 break-words font-medium">
                                                {row.items.length > 0
                                                    ? row.items.join(', ')
                                                    : '—'}
                                            </span>
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            <Badge
                                                variant="secondary"
                                                className="max-w-full whitespace-normal text-left"
                                            >
                                                {t(row.status_label)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {row.requested_at ?? '—'}
                                        </TableCell>
                                        <TableCell
                                            className={
                                                row.due_soon
                                                    ? 'font-semibold text-destructive'
                                                    : 'text-muted-foreground'
                                            }
                                        >
                                            {row.refund_due_by ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <DataPagination meta={paginated} />
            </div>
        </>
    );
}
