import { Head, Link, router } from '@inertiajs/react';
import { Building2, Check, Merge, Pencil, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ClubTranslationsDialog } from '@/components/admin/club-translations-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import sessionsRoutes from '@/routes/admin/community-sessions';
import type {
    AdminClubDetail,
    AdminClubSessionRow,
    ClubCornerLocaleCopy,
    ClubCornerTranslationStatus,
    MergeCandidate,
} from '@/types/club';

type FieldSource = 'survivor' | 'duplicate';
type ImageSource = FieldSource | 'none';

type SourceableField =
    | 'name'
    | 'street'
    | 'postal_code'
    | 'city'
    | 'country'
    | 'lat'
    | 'lng'
    | 'website'
    | 'phone'
    | 'status'
    | 'corner_pipeline_status'
    | 'corner_title'
    | 'corner_body'
    | 'corner_location'
    | 'sort_order';

const SOURCEABLE_FIELDS: SourceableField[] = [
    'name',
    'street',
    'postal_code',
    'city',
    'country',
    'lat',
    'lng',
    'website',
    'phone',
    'status',
    'corner_pipeline_status',
    'corner_title',
    'corner_body',
    'corner_location',
    'sort_order',
];

const FIELD_LABELS: Record<SourceableField, string> = {
    name: 'Naam',
    street: 'Straat',
    postal_code: 'Postcode',
    city: 'Stad',
    country: 'Land',
    lat: 'Latitude',
    lng: 'Longitude',
    website: 'Website',
    phone: 'Telefoon',
    status: 'Status',
    corner_pipeline_status: 'Pipeline status',
    corner_title: 'Corner titel',
    corner_body: 'Corner tekst',
    corner_location: 'Corner locatie',
    sort_order: 'Volgorde',
};

function emptySources(): Record<SourceableField, FieldSource> {
    return Object.fromEntries(
        SOURCEABLE_FIELDS.map((field) => [field, 'survivor']),
    ) as Record<SourceableField, FieldSource>;
}

function displayValue(
    club: AdminClubDetail | MergeCandidate,
    field: SourceableField,
    t: (key: string) => string,
): string {
    if (field === 'status') {
        return t(
            'status_label' in club
                ? club.status_label
                : club.status,
        );
    }

    if (field === 'corner_pipeline_status') {
        return club.corner_pipeline_label
            ? t(club.corner_pipeline_label)
            : '—';
    }

    const value = club[field];

    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

function unionSports(
    left: string[],
    right: string[],
): string[] {
    return Array.from(new Set([...left, ...right]));
}

export default function ClubShow({
    club,
    sessions,
    mergeCandidates,
    locales = [],
    translations = {},
    translationStatus = {},
}: {
    club: AdminClubDetail;
    sessions: AdminClubSessionRow[];
    mergeCandidates: MergeCandidate[];
    locales?: string[];
    translations?: Record<string, ClubCornerLocaleCopy>;
    translationStatus?: Record<string, ClubCornerTranslationStatus>;
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

                    {club.has_corner && (
                        <ClubTranslationsDialog
                            clubId={club.id}
                            locales={locales}
                            translations={translations}
                            translationStatus={translationStatus}
                        />
                    )}

                    <Button variant="outline" asChild>
                        <Link href={clubsRoutes.edit({ locale, club: club.id })}>
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
                                    <Badge
                                        variant="secondary"
                                        className="ml-2"
                                    >
                                        {t('Partner')}
                                    </Badge>
                                )}
                                {club.has_corner && (
                                    <Badge
                                        variant="secondary"
                                        className="ml-2"
                                    >
                                        {t('Club Corner')}
                                    </Badge>
                                )}
                                {club.show_on_corner_page && (
                                    <Badge
                                        variant="outline"
                                        className="ml-2"
                                    >
                                        {t('Corner pagina')}
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
                            <Detail label={t('Sessiezoekopdracht')}>
                                {club.is_session_venue
                                    ? t('Zichtbaar')
                                    : t('Verborgen')}
                            </Detail>
                            {club.corner_pipeline_label && (
                                <Detail label={t('Pipeline status')}>
                                    {t(club.corner_pipeline_label)}
                                </Detail>
                            )}
                            {club.has_corner && (
                                <>
                                    <Detail label={t('Corner titel')}>
                                        {club.corner_title ?? club.name}
                                    </Detail>
                                    <Detail label={t('Corner locatie')}>
                                        {club.corner_location ?? '—'}
                                    </Detail>
                                </>
                            )}
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
                                            {duplicate.name} ·{' '}
                                            {duplicate.city ?? '—'}
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
    const [sources, setSources] =
        useState<Record<SourceableField, FieldSource>>(emptySources);
    const [imageSource, setImageSource] = useState<ImageSource>('survivor');
    const [isPartner, setIsPartner] = useState(false);
    const [isSessionVenue, setIsSessionVenue] = useState(false);
    const [hasCorner, setHasCorner] = useState(false);
    const [cornerPublished, setCornerPublished] = useState(false);
    const [showOnCornerPage, setShowOnCornerPage] = useState(false);
    const [sports, setSports] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);

    const survivor = useMemo(
        () => candidates.find((candidate) => String(candidate.id) === targetId),
        [candidates, targetId],
    );

    useEffect(() => {
        if (!survivor) {
            return;
        }

        setSources(emptySources());
        setImageSource(
            survivor.image_url ? 'survivor' : club.image_url ? 'duplicate' : 'none',
        );
        setIsPartner(club.is_partner || survivor.is_partner);
        setIsSessionVenue(club.is_session_venue || survivor.is_session_venue);
        setHasCorner(club.has_corner || survivor.has_corner);
        setCornerPublished(club.corner_published || survivor.corner_published);
        setShowOnCornerPage(
            club.show_on_corner_page || survivor.show_on_corner_page,
        );
        setSports(unionSports(club.sports, survivor.sports));
    }, [survivor, club]);

    function setSource(field: SourceableField, value: FieldSource) {
        setSources((current) => ({ ...current, [field]: value }));
    }

    function toggleSport(sport: string, checked: boolean) {
        setSports((current) => {
            if (checked) {
                return unionSports(current, [sport]);
            }

            return current.filter((value) => value !== sport);
        });
    }

    function handleMerge() {
        if (!survivor || sports.length === 0) {
            return;
        }

        setProcessing(true);

        router.post(
            clubsRoutes.merge({ locale, club: club.id }).url,
            {
                target_id: survivor.id,
                sources,
                image_source: imageSource,
                is_partner: isPartner,
                is_session_venue: isSessionVenue,
                has_corner: hasCorner,
                corner_published: cornerPublished,
                show_on_corner_page: showOnCornerPage,
                sports,
            },
            {
                onFinish: () => setProcessing(false),
                onSuccess: () => setOpen(false),
            },
        );
    }

    const availableSports = survivor
        ? unionSports(club.sports, survivor.sports)
        : club.sports;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" disabled={candidates.length === 0}>
                    <Merge className="h-4 w-4" /> {t('Samenvoegen')}
                </Button>
            </DialogTrigger>

            <DialogContent className="flex max-h-[90vh] w-[min(100%-2rem,72rem)] flex-col gap-4 overflow-hidden sm:max-w-6xl">
                <DialogHeader>
                    <DialogTitle>{t('Clubs samenvoegen')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Alle sessies van “{{name}}” verhuizen naar de behouden club. “{{name}}” wordt permanent verwijderd.',
                            { name: club.name },
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="min-h-0 space-y-5 overflow-y-auto pr-1">
                    <div className="space-y-2">
                        <Label>{t('Behoud deze club')}</Label>
                        <Select value={targetId} onValueChange={setTargetId}>
                            <SelectTrigger
                                className="w-full"
                                aria-label={t('Behoud deze club')}
                            >
                                <SelectValue
                                    placeholder={t('Behoud deze club')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {candidates.map((candidate) => (
                                    <SelectItem
                                        key={candidate.id}
                                        value={String(candidate.id)}
                                    >
                                        {candidate.name} ·{' '}
                                        {candidate.city ?? '—'}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {survivor && (
                        <>
                            <div className="overflow-hidden rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                                            <TableHead>
                                                {t('Veld')}
                                            </TableHead>
                                            <TableHead>
                                                {club.name}
                                            </TableHead>
                                            <TableHead>
                                                {survivor.name}
                                            </TableHead>
                                            <TableHead className="w-36">
                                                {t('Keuze')}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {SOURCEABLE_FIELDS.map((field) => (
                                            <TableRow key={field}>
                                                <TableCell className="font-medium">
                                                    {t(FIELD_LABELS[field])}
                                                </TableCell>
                                                <TableCell className="max-w-[12rem] truncate text-muted-foreground">
                                                    {displayValue(
                                                        club,
                                                        field,
                                                        t,
                                                    )}
                                                </TableCell>
                                                <TableCell className="max-w-[12rem] truncate text-muted-foreground">
                                                    {displayValue(
                                                        survivor,
                                                        field,
                                                        t,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex gap-3 text-sm">
                                                        <label className="inline-flex items-center gap-1.5">
                                                            <input
                                                                type="radio"
                                                                name={`source-${field}`}
                                                                checked={
                                                                    sources[
                                                                        field
                                                                    ] ===
                                                                    'duplicate'
                                                                }
                                                                onChange={() =>
                                                                    setSource(
                                                                        field,
                                                                        'duplicate',
                                                                    )
                                                                }
                                                            />
                                                            A
                                                        </label>
                                                        <label className="inline-flex items-center gap-1.5">
                                                            <input
                                                                type="radio"
                                                                name={`source-${field}`}
                                                                checked={
                                                                    sources[
                                                                        field
                                                                    ] ===
                                                                    'survivor'
                                                                }
                                                                onChange={() =>
                                                                    setSource(
                                                                        field,
                                                                        'survivor',
                                                                    )
                                                                }
                                                            />
                                                            B
                                                        </label>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                        <TableRow>
                                            <TableCell className="font-medium">
                                                {t('Afbeelding')}
                                            </TableCell>
                                            <TableCell>
                                                {club.image_url ? (
                                                    <img
                                                        src={club.image_url}
                                                        alt=""
                                                        className="h-12 w-12 rounded object-cover"
                                                    />
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {survivor.image_url ? (
                                                    <img
                                                        src={
                                                            survivor.image_url
                                                        }
                                                        alt=""
                                                        className="h-12 w-12 rounded object-cover"
                                                    />
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1.5 text-sm">
                                                    <label className="inline-flex items-center gap-1.5">
                                                        <input
                                                            type="radio"
                                                            name="image_source"
                                                            checked={
                                                                imageSource ===
                                                                'duplicate'
                                                            }
                                                            onChange={() =>
                                                                setImageSource(
                                                                    'duplicate',
                                                                )
                                                            }
                                                        />
                                                        A
                                                    </label>
                                                    <label className="inline-flex items-center gap-1.5">
                                                        <input
                                                            type="radio"
                                                            name="image_source"
                                                            checked={
                                                                imageSource ===
                                                                'survivor'
                                                            }
                                                            onChange={() =>
                                                                setImageSource(
                                                                    'survivor',
                                                                )
                                                            }
                                                        />
                                                        B
                                                    </label>
                                                    <label className="inline-flex items-center gap-1.5">
                                                        <input
                                                            type="radio"
                                                            name="image_source"
                                                            checked={
                                                                imageSource ===
                                                                'none'
                                                            }
                                                            onChange={() =>
                                                                setImageSource(
                                                                    'none',
                                                                )
                                                            }
                                                        />
                                                        {t('Geen')}
                                                    </label>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <div className="space-y-3">
                                <p className="text-sm font-medium">
                                    {t('Functies')}
                                </p>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <FeatureToggle
                                        id="merge-is-partner"
                                        label={t('Partner')}
                                        checked={isPartner}
                                        onCheckedChange={setIsPartner}
                                    />
                                    <FeatureToggle
                                        id="merge-is-session-venue"
                                        label={t('Sessiezoekopdracht')}
                                        checked={isSessionVenue}
                                        onCheckedChange={setIsSessionVenue}
                                    />
                                    <FeatureToggle
                                        id="merge-has-corner"
                                        label={t('Club Corner')}
                                        checked={hasCorner}
                                        onCheckedChange={setHasCorner}
                                    />
                                    <FeatureToggle
                                        id="merge-corner-published"
                                        label={t('Corner gepubliceerd')}
                                        checked={cornerPublished}
                                        onCheckedChange={setCornerPublished}
                                    />
                                    <FeatureToggle
                                        id="merge-show-on-corner-page"
                                        label={t('Corner pagina')}
                                        checked={showOnCornerPage}
                                        onCheckedChange={setShowOnCornerPage}
                                    />
                                </div>
                            </div>

                            <div className="space-y-3">
                                <p className="text-sm font-medium">
                                    {t('Sporten')}
                                </p>
                                <div className="flex flex-wrap gap-4">
                                    {availableSports.map((sport) => (
                                        <label
                                            key={sport}
                                            className="inline-flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={sports.includes(
                                                    sport,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggleSport(
                                                        sport,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            {t(
                                                sport === 'padel'
                                                    ? 'Padel'
                                                    : 'Tennis',
                                            )}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </>
                    )}
                </div>

                <Button
                    type="button"
                    onClick={handleMerge}
                    disabled={
                        !survivor || sports.length === 0 || processing
                    }
                >
                    {t('Samenvoegen bevestigen')}
                </Button>
            </DialogContent>
        </Dialog>
    );
}

function FeatureToggle({
    id,
    label,
    checked,
    onCheckedChange,
}: {
    id: string;
    label: string;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
}) {
    return (
        <label
            htmlFor={id}
            className="inline-flex items-center gap-2 text-sm"
        >
            <Checkbox
                id={id}
                checked={checked}
                onCheckedChange={(value) =>
                    onCheckedChange(value === true)
                }
            />
            {label}
        </label>
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
