import { Head, Link, router } from '@inertiajs/react';
import { Ban, Users, UserX } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import sessionsRoutes from '@/routes/admin/community-sessions';
import participantRoutes from '@/routes/admin/community-sessions/participants';
import type {
    AdminSessionDetail,
    AdminSessionPlayer,
    SessionLifecycle,
} from '@/types/admin-session';

const LIFECYCLE_LABELS: Record<SessionLifecycle, string> = {
    active: 'Actief',
    ended: 'Beëindigd',
    cancelled: 'Geannuleerd',
};

export default function CommunitySessionShow({
    session,
}: {
    session: AdminSessionDetail;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    function cancelSession() {
        if (!window.confirm(t('Deze sessie annuleren?'))) {
            return;
        }

        router.patch(
            sessionsRoutes.cancel({
                locale,
                communitySession: Number(session.id),
            }).url,
            {},
            { preserveScroll: true },
        );
    }

    function removePlayer(player: AdminSessionPlayer) {
        if (
            !window.confirm(
                t('“{{name}}” uit deze sessie verwijderen?', {
                    name: player.name,
                }),
            )
        ) {
            return;
        }

        router.delete(
            participantRoutes.destroy({
                locale,
                communitySession: Number(session.id),
                user: Number(player.id),
            }).url,
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`${session.host} · ${session.club_name ?? ''}`} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={session.club_name ?? t('Sessie')}
                    description={t('Gehost door {{host}}', {
                        host: session.host,
                    })}
                    icon={Users}
                >
                    {session.lifecycle === 'active' && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={cancelSession}
                        >
                            <Ban className="h-4 w-4" /> {t('Sessie annuleren')}
                        </Button>
                    )}
                </AdminPageHeader>

                <div className="grid gap-4 rounded-xl border bg-card p-5 shadow-sm sm:grid-cols-3 lg:grid-cols-4">
                    <Detail label={t('Status')}>
                        <Badge
                            variant={
                                session.lifecycle === 'active'
                                    ? 'default'
                                    : 'outline'
                            }
                        >
                            {t(LIFECYCLE_LABELS[session.lifecycle])}
                        </Badge>
                    </Detail>
                    <Detail label={t('Sport')}>{t(session.sport_label)}</Detail>
                    <Detail label={t('Datum')}>
                        {new Date(session.starts_at).toLocaleString()}
                    </Detail>
                    <Detail label={t('Duur')}>
                        {session.duration_minutes} min
                    </Detail>
                    <Detail label={t('Niveau')}>
                        {t(session.level_label)}
                    </Detail>
                    <Detail label={t('Wie mag meespelen?')}>
                        {t(session.gender_label)}
                    </Detail>
                    <Detail label={t('Baanstatus')}>
                        {t(session.court_status_label)}
                    </Detail>
                    <Detail label={t('Club')}>
                        {session.club_id ? (
                            <Link
                                href={clubsRoutes.show({
                                    locale,
                                    club: session.club_id,
                                })}
                                className="underline underline-offset-4"
                            >
                                {session.club_name}
                            </Link>
                        ) : (
                            '—'
                        )}
                    </Detail>

                    {session.notes && (
                        <div className="sm:col-span-3 lg:col-span-4">
                            <Detail label={t('Notitie')}>
                                {session.notes}
                            </Detail>
                        </div>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="border-b px-5 py-4">
                        <h2 className="font-medium">{t('Spelers')}</h2>
                        <p className="text-sm text-muted-foreground">
                            {session.participants_count} / {session.capacity}
                        </p>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Naam')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('E-mailadres')}
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Aangesloten')}
                                </TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {session.players.map((player) => (
                                <TableRow key={player.id}>
                                    <TableCell className="font-medium">
                                        {player.name}
                                        {player.is_host && (
                                            <Badge
                                                variant="secondary"
                                                className="ml-2"
                                            >
                                                {t('Host')}
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="hidden text-muted-foreground sm:table-cell">
                                        {player.email}
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {player.joined_at
                                            ? new Date(
                                                  player.joined_at,
                                              ).toLocaleString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {!player.is_host && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                type="button"
                                                onClick={() =>
                                                    removePlayer(player)
                                                }
                                                title={t('Speler verwijderen')}
                                            >
                                                <UserX className="h-4 w-4" />
                                            </Button>
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

function Detail({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <div className="mt-1 text-sm">{children}</div>
        </div>
    );
}

CommunitySessionShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Sessies', href: sessionsRoutes.index(wayfinderLocale()) },
    ],
};
