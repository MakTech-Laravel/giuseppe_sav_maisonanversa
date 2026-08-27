import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    Check,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
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
import clubsRoutes from '@/routes/admin/clubs';
import type { Paginated } from '@/types/admin';
import type { AdminClubRow, ClubStatusOption } from '@/types/club';

interface ClubFilters {
    search: string | null;
    status: string | null;
    city: string | null;
}

export default function ClubsIndex({
    clubs: paginated,
    filters,
    cities,
    pendingCount,
    statusOptions,
}: {
    clubs: Paginated<AdminClubRow>;
    filters: ClubFilters;
    cities: string[];
    pendingCount: number;
    statusOptions: ClubStatusOption[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [city, setCity] = useState(filters.city || 'all');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                clubsRoutes.index(locale).url,
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    city: city === 'all' ? undefined : city,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, city, locale]);

    const hasActiveFilters =
        Boolean(search) || status !== 'all' || city !== 'all';

    function clearFilters() {
        setSearch('');
        setStatus('all');
        setCity('all');
    }

    function approve(clubId: number) {
        router.patch(
            clubsRoutes.approve({ locale, club: clubId }).url,
            {},
            { preserveScroll: true },
        );
    }

    function reject(clubId: number) {
        router.patch(
            clubsRoutes.reject({ locale, club: clubId }).url,
            {},
            { preserveScroll: true },
        );
    }

    function destroyClub(club: AdminClubRow) {
        if (
            !window.confirm(
                t('“{{name}}” definitief verwijderen?', { name: club.name }),
            )
        ) {
            return;
        }

        router.delete(clubsRoutes.destroy({ locale, club: club.id }).url);
    }

    return (
        <>
            <Head title={t('Clubs')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Clubs')}
                    description={t(
                        'Beheer de clubs en corners waar leden sessies plannen.',
                    )}
                    icon={Building2}
                >
                    <Button asChild>
                        <Link href={clubsRoutes.create(locale)}>
                            <Plus className="h-4 w-4" /> {t('Club toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                {pendingCount > 0 && (
                    <button
                        type="button"
                        onClick={() => setStatus('pending')}
                        className="w-full cursor-pointer rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-left text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        {t(
                            '{{count}} door leden ingediende clubs wachten op goedkeuring.',
                            { count: pendingCount },
                        )}
                    </button>
                )}

                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_11rem_11rem_auto] sm:items-center">
                    <div className="relative min-w-0">
                        <Label htmlFor="clubs-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="clubs-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op naam, stad, postcode…')}
                            className="w-full pl-9"
                            aria-label={t('Zoeken')}
                        />
                    </div>

                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Status')}
                        >
                            <SelectValue placeholder={t('Alle statussen')} />
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
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={city} onValueChange={setCity}>
                        <SelectTrigger className="w-full" aria-label={t('Stad')}>
                            <SelectValue placeholder={t('Alle steden')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle steden')}
                            </SelectItem>
                            {cities.map((option) => (
                                <SelectItem key={option} value={option}>
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {hasActiveFilters ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="justify-self-start"
                            onClick={clearFilters}
                        >
                            <X className="h-4 w-4" />
                            {t('Wis filters')}
                        </Button>
                    ) : (
                        <span className="hidden sm:block" aria-hidden />
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Club')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Adres')}
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    {t('Sporten')}
                                </TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Sessies')}
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
                                        colSpan={6}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen clubs gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((club) => (
                                    <TableRow key={club.id}>
                                        <TableCell>
                                            <span className="font-medium">
                                                {club.name}
                                            </span>
                                            {club.is_partner && (
                                                <Badge
                                                    variant="secondary"
                                                    className="ml-2"
                                                >
                                                    {t('Partner')}
                                                </Badge>
                                            )}
                                            <span className="block text-xs text-muted-foreground md:hidden">
                                                {club.city}
                                            </span>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell text-muted-foreground">
                                            {club.address}
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell text-muted-foreground">
                                            {club.sports
                                                .map((sport) =>
                                                    t(
                                                        sport === 'padel'
                                                            ? 'Padel'
                                                            : 'Tennis',
                                                    ),
                                                )
                                                .join(', ')}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    club.status === 'approved'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {t(club.status_label)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {club.sessions_count}
                                        </TableCell>
                                        <TableCell className="space-x-1 text-right">
                                            {club.status === 'pending' && (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        type="button"
                                                        onClick={() =>
                                                            approve(club.id)
                                                        }
                                                        title={t('Goedkeuren')}
                                                    >
                                                        <Check className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        type="button"
                                                        onClick={() =>
                                                            reject(club.id)
                                                        }
                                                        title={t('Afwijzen')}
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </Button>
                                                </>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={clubsRoutes.show({
                                                        locale,
                                                        club: club.id,
                                                    })}
                                                    title={t('Bekijken')}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={clubsRoutes.edit({
                                                        locale,
                                                        club: club.id,
                                                    })}
                                                    title={t('Bewerken')}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                type="button"
                                                onClick={() =>
                                                    destroyClub(club)
                                                }
                                                title={t('Verwijderen')}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

ClubsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Clubs', href: clubsRoutes.index(wayfinderLocale()) },
    ],
};
