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

            <div className="mb-4 flex flex-wrap gap-3">
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={t('Zoeken')}
                    className="max-w-xs"
                />
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="w-48">
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

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Nummer')}</TableHead>
                        <TableHead>{t('Bestelling')}</TableHead>
                        <TableHead>{t('Klant')}</TableHead>
                        <TableHead>{t('Status')}</TableHead>
                        <TableHead>{t('Aangevraagd')}</TableHead>
                        <TableHead>{t('Terugbetaling vóór')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {paginated.data.map((row) => (
                        <TableRow key={row.id}>
                            <TableCell>
                                <Link
                                    href={`/${locale}/admin/returns/${row.id}`}
                                    className="font-medium underline"
                                >
                                    {row.number}
                                </Link>
                                {row.maison_pays_return_shipping ? (
                                    <Badge className="ml-2" variant="outline">
                                        {t('Onze verzending')}
                                    </Badge>
                                ) : null}
                            </TableCell>
                            <TableCell>{row.order_reference}</TableCell>
                            <TableCell>{row.customer}</TableCell>
                            <TableCell>{t(row.status_label)}</TableCell>
                            <TableCell>{row.requested_at}</TableCell>
                            <TableCell
                                className={
                                    row.due_soon ? 'font-semibold text-red-600' : ''
                                }
                            >
                                {row.refund_due_by}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>

            <DataPagination paginated={paginated} />
        </>
    );
}
