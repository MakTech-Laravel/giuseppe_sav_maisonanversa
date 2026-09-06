import { Head, Link, router } from '@inertiajs/react';
import { Building2, Check, Merge, Pencil, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import sessionsRoutes from '@/routes/admin/community-sessions';
import type {
    AdminClubDetail,
    AdminClubSessionRow,
    MergeCandidate,
} from '@/types/club';

export default function ClubShow({
    club,
    sessions,
    mergeCandidates,
}: {
    club: AdminClubDetail;
    sessions: AdminClubSessionRow[];
    mergeCandidates: MergeCandidate[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    function approve() {
        router.patch(
            clubsRoutes.approve({ locale, club: club.id }).url,
            {},
            { preserveScroll: true },
        );
    }

    function reject() {
        router.patch(
            clubsRoutes.reject({ locale, club: club.id }).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={club.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={club.name}
                    description={club.address}
                    icon={Building2}
                >
                    {club.status === 'pending' && (
                        <>
                            <Button type="button" onClick={approve}>
                                <Check className="h-4 w-4" /> {t('Goedkeuren')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={reject}
                            >
                                <X className="h-4 w-4" /> {t('Afwijzen')}
                            </Button>
                        </>
                    )}

                    <MergeDialog club={club} candidates={mergeCandidates} />

                    <Button variant="outline" asChild>
                        <Link
                            href={clubsRoutes.edit({ locale, club: club.id })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="space-y-4 rounded-xl border bg-card p-5 shadow-sm md:col-span-2">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Detail label={t('Status')}>
                                <Badge
                                    variant={
                                        club.status === 'approved'
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    {t(club.status_label)}
                                </Badge>
                                {club.is_partner && (
                                    <Badge variant="secondary" className="ml-2">
                                        {t('Partner')}
                                    </Badge>
                                )}
                            </Detail>
                            <Detail label={t('Sporten')}>
                                {club.sports
                                    .map((sport) =>
                                        t(
                                            sport === 'padel'
                                                ? 'Padel'
                                                : 'Tennis',
                                        ),
                                    )
                                    .join(', ')}
                            </Detail>
                            <Detail label={t('Website')}>
                                {club.website ? (
                                    <a
                                        href={club.website}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="underline underline-offset-4"
                                    >
                                        {club.website}
                                    </a>
                                ) : (
                                    '—'
                                )}
                            </Detail>
                            <Detail label={t('Telefoon')}>
                                {club.phone ?? '—'}
                            </Detail>
                            <Detail label={t('Ingediend door')}>
                                {club.submitted_by
                                    ? `${club.submitted_by.name} (${club.submitted_by.email})`
                                    : t('Maison Anversa')}
                            </Detail>
                            <Detail label={t('Goedgekeurd door')}>
                                {club.approved_by?.name ?? '—'}
                            </Detail>
                        </dl>

                        {club.merged_into && (
                            <p className="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                                {t('Samengevoegd met')}{' '}
                                <Link
                                    href={clubsRoutes.show({
                                        locale,
                                        club: club.merged_into.id,
                                    })}
                                    className="underline underline-offset-4"
                                >
                                    {club.merged_into.name}
                                </Link>
                            </p>
                        )}

                        {club.duplicates.length > 0 && (
                            <div className="space-y-1 text-sm">
                                <p className="font-medium">
                                    {t('Samengevoegde duplicaten')}
                                </p>
                                <ul className="text-muted-foreground">
                                    {club.duplicates.map((duplicate) => (
                                        <li key={duplicate.id}>
                                            {duplicate.name} · {duplicate.city}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>

                    {club.image_url ? (
                        <img
                            src={club.image_url}
                            alt=""
                            className="h-56 w-full rounded-xl border object-cover shadow-sm"
                        />
                    ) : (
                        <div className="flex h-56 items-center justify-center rounded-xl border bg-muted text-sm text-muted-foreground">
                            {t('Geen afbeelding')}
                        </div>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="border-b px-5 py-4">
                        <h2 className="font-medium">{t('Sessies')}</h2>
                        <p className="text-sm text-muted-foreground">
                            {t('{{count}} sessies bij deze club.', {
                                count: club.sessions_count,
                            })}
                        </p>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Host')}</TableHead>
                                <TableHead>{t('Sport')}</TableHead>
                                <TableHead>{t('Datum')}</TableHead>
                                <TableHead>{t('Spelers')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sessions.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen sessies gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                sessions.map((session) => (
                                    <TableRow key={session.id}>
                                        <TableCell>{session.host}</TableCell>
                                        <TableCell>
                                            {t(session.sport_label)}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(
                                                session.starts_at,
                                            ).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            {session.participants_count} /{' '}
                                            {session.capacity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={sessionsRoutes.show({
                                                        locale,
                                                        communitySession:
                                                            Number(session.id),
                                                    })}
                                                >
                                                    {t('Bekijken')}
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

function MergeDialog({
    club,
    candidates,
}: {
    club: AdminClubDetail;
    candidates: MergeCandidate[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [targetId, setTargetId] = useState('');

    function handleMerge() {
        router.post(
            clubsRoutes.merge({ locale, club: club.id }).url,
            { target_id: Number(targetId) },
            { onSuccess: () => setOpen(false) },
        );
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" disabled={candidates.length === 0}>
                    <Merge className="h-4 w-4" /> {t('Samenvoegen')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Clubs samenvoegen')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Alle sessies van “{{name}}” verhuizen naar de gekozen club. “{{name}}” blijft bewaard als duplicaat en verdwijnt uit de zoekresultaten.',
                            { name: club.name },
                        )}
                    </DialogDescription>
                </DialogHeader>

                <Select value={targetId} onValueChange={setTargetId}>
                    <SelectTrigger
                        className="w-full"
                        aria-label={t('Behoud deze club')}
                    >
                        <SelectValue placeholder={t('Behoud deze club')} />
                    </SelectTrigger>
                    <SelectContent>
                        {candidates.map((candidate) => (
                            <SelectItem
                                key={candidate.id}
                                value={String(candidate.id)}
                            >
                                {candidate.name} · {candidate.city}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Button
                    type="button"
                    onClick={handleMerge}
                    disabled={targetId === ''}
                >
                    {t('Samenvoegen bevestigen')}
                </Button>
            </DialogContent>
        </Dialog>
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
            <dt className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-1 text-sm">{children}</dd>
        </div>
    );
}

ClubShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Clubs', href: clubsRoutes.index(wayfinderLocale()) },
    ],
};
