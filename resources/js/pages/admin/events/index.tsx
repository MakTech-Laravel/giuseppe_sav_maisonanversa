import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarDays,
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
import eventsRoutes from '@/routes/admin/events';
import type { Paginated } from '@/types/admin';

type EventLifecycleStatus = 'opening' | 'ongoing' | 'closed';

interface EventRow {
    id: string;
    title: string;
    starts_at: string;
    location: string;
    capacity: number | null;
    rsvp_count: number;
    thumbnail_url: string | null;
    status: EventLifecycleStatus;
}

interface EventFilters {
    search: string;
    status: string;
}

function formatStartsAt(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}

function statusLabel(
    status: EventLifecycleStatus,
    t: (key: string) => string,
): string {
    switch (status) {
        case 'opening':
            return t('Opening');
        case 'ongoing':
            return t('Lopend');
        case 'closed':
            return t('Gesloten');
    }
}

function eventsQuery(filters: EventFilters): Record<string, string | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
    };
}

export default function EventsIndex({
    events: paginated,
    filters,
}: {
    events: Paginated<EventRow>;
    filters: EventFilters;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
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
                eventsRoutes.index(locale).url,
                eventsQuery({
                    search,
                    status: status === 'all' ? '' : status,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, locale]);

    const hasActiveFilters = Boolean(search) || status !== 'all';

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
    };

    function destroyEvent(eventId: string) {
        if (!window.confirm(t('Evenement verwijderen?'))) {
            return;
        }

        router.delete(
            eventsRoutes.destroy({
                locale: wayfinderLocale(),
                event: Number(eventId),
            }).url,
        );
    }

    function updateEventStatus(
        eventId: string,
        nextStatus: EventLifecycleStatus,
    ) {
        router.patch(
            eventsRoutes.status({
                locale: wayfinderLocale(),
                event: Number(eventId),
            }).url,
            { status: nextStatus },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={t('Evenementen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Evenementen')}
                    description={t(
                        'Bekijk geplande Maison-evenementen en aanwezigheid.',
                    )}
                    icon={CalendarDays}
                >
                    <Button asChild>
                        <Link href={eventsRoutes.create(wayfinderLocale())}>
                            <Plus className="h-4 w-4" />{' '}
                            {t('Evenement toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_11rem_auto] sm:items-center">
                    <div className="relative min-w-0">
                        <Label htmlFor="events-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="events-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op titel, locatie…')}
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
                            <SelectItem value="opening">
                                {t('Opening')}
                            </SelectItem>
                            <SelectItem value="ongoing">
                                {t('Lopend')}
                            </SelectItem>
                            <SelectItem value="closed">
                                {t('Gesloten')}
                            </SelectItem>
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
                                <TableHead className="w-14" />
                                <TableHead>{t('Evenement')}</TableHead>
                                <TableHead className="w-44 min-w-40">
                                    {t('Status')}
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Datum')}
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    {t('Locatie')}
                                </TableHead>
                                <TableHead>{t('Aanwezigen')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen evenementen gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((event) => (
                                    <TableRow key={event.id}>
                                        <TableCell>
                                            {event.thumbnail_url ? (
                                                <img
                                                    src={event.thumbnail_url}
                                                    alt=""
                                                    className="size-10 rounded object-cover"
                                                />
                                            ) : (
                                                <div className="size-10 rounded bg-muted" />
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-medium">
                                                {event.title}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Select
                                                value={event.status}
                                                onValueChange={(value) =>
                                                    updateEventStatus(
                                                        event.id,
                                                        value as EventLifecycleStatus,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    className="h-8 w-full max-w-[11rem]"
                                                    aria-label={t(
                                                        'Status wijzigen',
                                                    )}
                                                >
                                                    <SelectValue>
                                                        {statusLabel(
                                                            event.status,
                                                            t,
                                                        )}
                                                    </SelectValue>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="opening">
                                                        {t('Opening')}
                                                    </SelectItem>
                                                    <SelectItem value="ongoing">
                                                        {t('Lopend')}
                                                    </SelectItem>
                                                    <SelectItem value="closed">
                                                        {t('Gesloten')}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {formatStartsAt(event.starts_at)}
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {event.location}
                                        </TableCell>
                                        <TableCell>
                                            {event.rsvp_count}
                                            {event.capacity != null
                                                ? ` / ${event.capacity}`
                                                : ''}
                                        </TableCell>
                                        <TableCell className="space-x-1 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={eventsRoutes.show({
                                                        locale: wayfinderLocale(),
                                                        event: Number(
                                                            event.id,
                                                        ),
                                                    })}
                                                    title={t(
                                                        'Boekingen bekijken',
                                                    )}
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
                                                    href={eventsRoutes.edit({
                                                        locale: wayfinderLocale(),
                                                        event: Number(
                                                            event.id,
                                                        ),
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
                                                    destroyEvent(event.id)
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

EventsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Evenementen', href: eventsRoutes.index(wayfinderLocale()) },
    ],
};
