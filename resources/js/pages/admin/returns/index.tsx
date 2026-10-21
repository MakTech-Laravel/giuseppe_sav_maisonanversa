import { Head, Link, router } from '@inertiajs/react';
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
            <AdminPageHeader
                title={t('Retours')}
                description={t('Retouraanvragen van klanten')}
            />

            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={t('Zoeken')}
                    className="w-full sm:max-w-xs"
                />
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="w-full sm:w-48">
                        <SelectValue placeholder={t('Status')} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{t('Alle')}</SelectItem>
                        {statuses.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {t(option.label)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button type="button" onClick={apply}>
                    {t('Filter')}
                </Button>
            </div>

            <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Nummer')}</TableHead>
                                <TableHead>{t('Bestelling')}</TableHead>
                                <TableHead>{t('Klant')}</TableHead>
                                <TableHead>{t('Artikelen')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Aangevraagd')}</TableHead>
                                <TableHead>{t('Terugbetaling vóór')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {t('Geen retours gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Link
                                                    href={`/${locale}/admin/returns/${row.id}`}
                                                    className="font-medium underline underline-offset-4"
                                                >
                                                    {row.number}
                                                </Link>
                                                {row.maison_pays_return_shipping ? (
                                                    <Badge variant="outline">
                                                        {t('Onze verzending')}
                                                    </Badge>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {row.order_reference}
                                        </TableCell>
                                        <TableCell>{row.customer}</TableCell>
                                        <TableCell className="max-w-48 truncate">
                                            {row.items.length > 0
                                                ? row.items.join(', ')
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {t(row.status_label)}
                                        </TableCell>
                                        <TableCell>
                                            {row.requested_at}
                                        </TableCell>
                                        <TableCell
                                            className={
                                                row.due_soon
                                                    ? 'font-semibold text-red-600'
                                                    : ''
                                            }
                                        >
                                            {row.refund_due_by}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <div className="mt-4">
                <DataPagination meta={paginated} />
            </div>
        </>
    );
}
