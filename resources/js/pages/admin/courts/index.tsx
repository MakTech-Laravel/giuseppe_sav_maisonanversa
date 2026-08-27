import { Head, Link, router } from '@inertiajs/react';
import { Eye, MapPin, Pencil, Plus, Search, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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
import courtsRoutes from '@/routes/admin/courts';
import type { Paginated } from '@/types/admin';

interface CourtRow {
    id: string;
    title: string;
    location: string;
    sort_order: number;
    is_published: boolean;
}

interface CourtFilters {
    search: string;
    status: string;
    per_page: number;
}

const DEFAULT_PER_PAGE = 15;

function emptyPaginated(): Paginated<CourtRow> {
    return {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: DEFAULT_PER_PAGE,
        from: null,
        to: null,
        total: 0,
        links: [],
        prev_page_url: null,
        next_page_url: null,
    };
}

function normalizeCourts(
    courts: Paginated<CourtRow> | CourtRow[] | undefined,
): Paginated<CourtRow> {
    if (courts == null) {
        return emptyPaginated();
    }

    if (Array.isArray(courts)) {
        return {
            data: courts,
            current_page: 1,
            last_page: 1,
            per_page: courts.length || DEFAULT_PER_PAGE,
            from: courts.length > 0 ? 1 : null,
            to: courts.length > 0 ? courts.length : null,
            total: courts.length,
            links: [],
            prev_page_url: null,
            next_page_url: null,
        };
    }

    return {
        ...emptyPaginated(),
        ...courts,
        data: Array.isArray(courts.data) ? courts.data : [],
        links: Array.isArray(courts.links) ? courts.links : [],
    };
}

function courtQuery(
    filters: CourtFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function CourtsIndex({
    courts,
    filters = { search: '', status: '', per_page: DEFAULT_PER_PAGE },
    perPageOptions = [10, 15, 25, 50, 100],
}: {
    courts?: Paginated<CourtRow> | CourtRow[];
    filters?: CourtFilters;
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const paginated = normalizeCourts(courts);
    const [search, setSearch] = useState(filters.search ?? '');
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
                courtsRoutes.index(locale).url,
                courtQuery({
                    search,
                    status: status === 'all' ? '' : status,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, perPage, locale]);

    const hasActiveFilters = Boolean(search) || status !== 'all';

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
    };

    return (
        <>
            <Head title={t('Club Corners')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Club Corners')}
                    description={t(
                        'Beheer gepubliceerde Club Corners op de communitykaart.',
                    )}
                    icon={MapPin}
                >
                    <Button asChild>
                        <Link href={courtsRoutes.create(locale)}>
                            <Plus className="h-4 w-4" /> {t('Corner toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="relative sm:col-span-2">
                        <Label htmlFor="court-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="court-search"
                            className="pl-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t(
                                'Zoek op titel, beschrijving of locatie…',
                            )}
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
                            <SelectItem value="published">
                                {t('Gepubliceerd')}
                            </SelectItem>
                            <SelectItem value="draft">
                                {t('Concept')}
                            </SelectItem>
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
                    {hasActiveFilters ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={clearFilters}
                            className="w-full sm:col-span-2 lg:col-span-4 lg:w-auto lg:justify-self-start"
                        >
                            <X className="h-4 w-4" />
                            {t('Filters wissen')}
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Titel')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Locatie')}
                                </TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Volgorde')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen Club Corners gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((court) => (
                                    <TableRow key={court.id}>
                                        <TableCell className="font-medium">
                                            {court.title}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {court.location || '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {court.is_published
                                                    ? t('Gepubliceerd')
                                                    : t('Concept')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{court.sort_order}</TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={courtsRoutes.show({
                                                        locale,
                                        court: Number(court.id),
                                    })}
                                >
                                    <Eye className="h-4 w-4" />
                                    {t('Bekijken')}
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={courtsRoutes.edit({
                                                        locale,
                                        court: Number(court.id),
                                    })}
                                >
                                    <Pencil className="h-4 w-4" />
                                    {t('Bewerken')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'Deze Club Corner wordt permanent verwijderd.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                    courtsRoutes.destroy({
                                        locale,
                                        court: Number(court.id),
                                    }).url,
                                                    )
                                                }
                                            >
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    {t('Verwijderen')}
                                                </Button>
                                            </ConfirmDeleteDialog>
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

CourtsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Club Corners', href: courtsRoutes.index(wayfinderLocale()) },
    ],
};
