import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, BookText, Download } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import circleRoutes from '@/routes/admin/circle';

interface AdminPlace {
    id: string | null;
    number: string;
    sequence: number;
    state: 'inscribed' | 'available' | 'archive';
    member_name: string | null;
    email: string | null;
    visibility: string | null;
    visibility_label: string | null;
    consent_at: string | null;
    hidden: boolean;
    user_id: number | null;
}

export default function CircleRegister({
    entries,
    filters,
}: {
    entries: AdminPlace[];
    filters: { search: string; status: string };
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search);

    function applyFilters(event: FormEvent, status = filters.status) {
        event.preventDefault();

        router.get(
            circleRoutes.register({ locale }).url,
            { search, status },
            { preserveState: true, preserveScroll: true },
        );
    }

    function changeNumber(entry: AdminPlace, editionNumber: string) {
        if (entry.id === null) {
            return;
        }

        router.patch(
            `/${locale}/admin/circle/register/${entry.id}`,
            { edition_number: Number(editionNumber) },
            { preserveScroll: true },
        );
    }

    function toggleHidden(entry: AdminPlace) {
        if (entry.id === null) {
            return;
        }

        router.patch(
            `/${locale}/admin/circle/register/${entry.id}/visibility`,
            { hidden: !entry.hidden },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={t('Naamregister')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Naamregister')}
                    description={t(
                        'Alle honderd plaatsen van Heritage No.001. Verberg een vermelding, wijzig het nummer, of exporteer het register.',
                    )}
                    icon={BookText}
                >
                    <Button variant="outline" asChild>
                        <a href={`/${locale}/admin/circle/register/export`}>
                            <Download className="h-4 w-4" /> {t('CSV exporteren')}
                        </a>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.index({ locale })}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar Founding Circle')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-5 shadow-sm sm:flex-row sm:items-end"
                >
                    <div className="flex-1 space-y-1">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op nummer, naam of e-mail')}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {(
                            [
                                ['all', t('Alle 100')],
                                ['inscribed', t('Ingeschreven')],
                                ['available', t('Beschikbaar')],
                                ['archive', t('Niet te koop')],
                            ] as const
                        ).map(([value, label]) => (
                            <Button
                                key={value}
                                type="button"
                                variant={
                                    filters.status === value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={(event) => applyFilters(event, value)}
                            >
                                {label}
                            </Button>
                        ))}
                    </div>
                </form>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Nummer')}</TableHead>
                                <TableHead>{t('Lid')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Zichtbaarheid')}
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    {t('Toestemming')}
                                </TableHead>
                                <TableHead>{t('Verborgen')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <TableRow key={entry.number}>
                                    <TableCell className="font-medium">
                                        № {entry.number}
                                    </TableCell>
                                    <TableCell>
                                        <div>{entry.member_name ?? '—'}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {entry.email ?? t(stateLabel(entry.state))}
                                        </div>
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {entry.visibility_label
                                            ? t(entry.visibility_label)
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="hidden lg:table-cell">
                                        {entry.consent_at ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {entry.state === 'inscribed' ? (
                                            <Badge
                                                variant={
                                                    entry.hidden
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                            >
                                                {entry.hidden
                                                    ? t('Verborgen')
                                                    : t('Zichtbaar')}
                                            </Badge>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {entry.id !== null && (
                                            <div className="flex flex-wrap items-center justify-end gap-2">
                                                <form
                                                    className="flex items-center gap-2"
                                                    onSubmit={(event) => {
                                                        event.preventDefault();
                                                        const value =
                                                            new FormData(
                                                                event.currentTarget,
                                                            ).get(
                                                                'edition_number',
                                                            );
                                                        changeNumber(
                                                            entry,
                                                            String(value ?? ''),
                                                        );
                                                    }}
                                                >
                                                    <Input
                                                        name="edition_number"
                                                        type="number"
                                                        min={1}
                                                        max={100}
                                                        defaultValue={
                                                            entry.sequence
                                                        }
                                                        className="w-20"
                                                        aria-label={t('Nummer')}
                                                    />
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        {t('Nummer')}
                                                    </Button>
                                                </form>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        toggleHidden(entry)
                                                    }
                                                >
                                                    {entry.hidden
                                                        ? t('Toon weer')
                                                        : t('Verberg')}
                                                </Button>
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

function stateLabel(state: AdminPlace['state']): string {
    if (state === 'archive') {
        return 'Niet te koop';
    }

    if (state === 'available') {
        return 'Beschikbaar';
    }

    return 'Ingeschreven';
}
