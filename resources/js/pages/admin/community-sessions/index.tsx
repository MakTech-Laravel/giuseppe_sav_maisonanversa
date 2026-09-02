import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search, Trash2, Users, X } from 'lucide-react';
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
import sessionsRoutes from '@/routes/admin/community-sessions';
import type { Paginated } from '@/types/admin';
import type { AdminSessionRow, SessionLifecycle } from '@/types/admin-session';

interface SessionFilters {
    search: string | null;
    sport: string | null;
    status: string | null;
    club_id: number | null;
}

const LIFECYCLE_LABELS: Record<SessionLifecycle, string> = {
    active: 'Actief',
    ended: 'Beëindigd',
    cancelled: 'Geannuleerd',
};

export default function CommunitySessionsIndex({
    sessions: paginated,
    filters,
    sportOptions,
    clubOptions,
}: {
    sessions: Paginated<AdminSessionRow>;
    filters: SessionFilters;
    sportOptions: { value: string; label: string }[];
    clubOptions: { id: number; name: string }[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [sport, setSport] = useState(filters.sport || 'all');
    const [status, setStatus] = useState(filters.status || 'all');
    const [clubId, setClubId] = useState(
        filters.club_id != null ? String(filters.club_id) : 'all',
    );
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                sessionsRoutes.index(locale).url,
                {
                    search: search || undefined,
                    sport: sport === 'all' ? undefined : sport,
                    status: status === 'all' ? undefined : status,
                    club_id: clubId === 'all' ? undefined : clubId,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, sport, status, clubId, locale]);

    const hasActiveFilters =
        Boolean(search) ||
        sport !== 'all' ||
        status !== 'all' ||
        clubId !== 'all';

    function clearFilters() {
        setSearch('');
        setSport('all');
        setStatus('all');
        setClubId('all');
    }

    function destroySession(sessionId: string) {
        if (!window.confirm(t('Sessie definitief verwijderen?'))) {
            return;
        }

        router.delete(
            sessionsRoutes.destroy({
                locale,
                communitySession: Number(sessionId),
            }).url,
        );
    }

    return (
        <>
            <Head title={t('Sessies')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Sessies')}
                    description={t(
                        'Alle door leden geplande sessies, inclusief beëindigde en geannuleerde.',
                    )}
                    icon={Users}
                />

                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_9rem_9rem_12rem_auto] sm:items-center">
                    <div className="relative min-w-0">
                        <Label htmlFor="sessions-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="sessions-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op host of club…')}
                            className="w-full pl-9"
                            aria-label={t('Zoeken')}
                        />
                    </div>

                    <Select value={sport} onValueChange={setSport}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Sport')}
                        >
                            <SelectValue placeholder={t('Alle sporten')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle sporten')}
                            </SelectItem>
                            {sportOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

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
                            <SelectItem value="active">
                                {t('Actief')}
                            </SelectItem>
                            <SelectItem value="ended">
                                {t('Beëindigd')}
                            </SelectItem>
                            <SelectItem value="cancelled">
                                {t('Geannuleerd')}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select value={clubId} onValueChange={setClubId}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Club')}
                        >
                            <SelectValue placeholder={t('Alle clubs')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle clubs')}
                            </SelectItem>
                            {clubOptions.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.name}
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
                                <TableHead>{t('Host')}</TableHead>
                                <TableHead>{t('Club')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Sport')}
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Datum')}
                                </TableHead>
                                <TableHead>{t('Spelers')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
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
                                        {t('Geen sessies gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((session) => (
                                    <TableRow key={session.id}>
                                        <TableCell className="font-medium">
                                            {session.host}
                                        </TableCell>
                                        <TableCell>
                                            {session.club_name ?? '—'}
                                            {session.club_city && (
                                                <span className="block text-xs text-muted-foreground">
                                                    {session.club_city}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {t(session.sport_label)}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {new Date(
                                                session.starts_at,
                                            ).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            {session.participants_count} /{' '}
                                            {session.capacity}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    session.lifecycle ===
                                                    'active'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {t(
                                                    LIFECYCLE_LABELS[
                                                        session.lifecycle
                                                    ],
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="space-x-1 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={sessionsRoutes.show({
                                                        locale,
                                                        communitySession:
                                                            Number(session.id),
                                                    })}
                                                    title={t('Bekijken')}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                type="button"
                                                onClick={() =>
                                                    destroySession(session.id)
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

CommunitySessionsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Sessies', href: sessionsRoutes.index(wayfinderLocale()) },
    ],
};
