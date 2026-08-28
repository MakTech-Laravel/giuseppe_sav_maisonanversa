import { Head, router } from '@inertiajs/react';
import { Download, Mail, Search, TriangleAlert, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DataPagination } from '@/components/admin/data-pagination';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import letter from '@/routes/admin/letter';
import type { Paginated } from '@/types/admin';

interface SubscriberRow {
    id: number;
    email: string;
    name: string;
    status: string;
    source: string;
    locale: string;
    joined_at: string | null;
    synced_at: string | null;
    preferences: {
        heritageLetter: boolean;
        productUpdates: boolean;
        events: boolean;
    };
}

interface LetterFilters {
    search: string;
    status: string;
    source: string;
    locale: string;
    per_page: number;
}

const DEFAULT_PER_PAGE = 15;

const STATUS_OPTIONS = [
    { value: 'pending', label: 'In behandeling' },
    { value: 'subscribed', label: 'Actief' },
    { value: 'unsubscribed', label: 'Uitgeschreven' },
] as const;

const SOURCE_OPTIONS = [
    { value: 'home', label: 'Home' },
    { value: 'story', label: 'Verhaal' },
    { value: 'modal', label: 'Modal' },
    { value: 'waitlist', label: 'Wachtlijst' },
    { value: 'sold_out', label: 'Uitverkocht' },
    { value: 'member', label: 'Lid' },
] as const;

const LOCALE_OPTIONS = [
    { value: 'nl', label: 'NL' },
    { value: 'en', label: 'EN' },
    { value: 'fr', label: 'FR' },
] as const;

function letterQuery(
    filters: LetterFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
        source: filters.source || undefined,
        subscriber_locale: filters.locale || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

function translateSubscriberStatus(
    status: string,
    t: (key: string) => string,
): string {
    const match = STATUS_OPTIONS.find((option) => option.value === status);

    return t(match?.label ?? status);
}

function translateSubscriberSource(
    source: string,
    t: (key: string) => string,
): string {
    const match = SOURCE_OPTIONS.find((option) => option.value === source);

    return t(match?.label ?? source);
}

function preferenceBadges(
    preferences: SubscriberRow['preferences'],
    t: (key: string) => string,
): { key: string; label: string }[] {
    const badges = [
        preferences.heritageLetter
            ? { key: 'heritageLetter', label: t('Heritage Letter') }
            : null,
        preferences.productUpdates
            ? { key: 'productUpdates', label: t('Productupdates') }
            : null,
        preferences.events ? { key: 'events', label: t('Sessies & events') } : null,
    ];

    return badges.filter(
        (badge): badge is { key: string; label: string } => badge !== null,
    );
}

export default function LetterIndex({
    subscribers: paginated,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
    letterConnected,
}: {
    subscribers: Paginated<SubscriberRow>;
    filters: LetterFilters;
    perPageOptions?: number[];
    letterConnected: boolean;
}) {
    const { t } = useTranslation();
    const uiLocale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [source, setSource] = useState(filters.source || 'all');
    const [subscriberLocale, setSubscriberLocale] = useState(
        filters.locale || 'all',
    );
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
                letter.index(uiLocale).url,
                letterQuery({
                    search,
                    status: status === 'all' ? '' : status,
                    source: source === 'all' ? '' : source,
                    locale:
                        subscriberLocale === 'all' ? '' : subscriberLocale,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, source, subscriberLocale, perPage, uiLocale]);

    const hasActiveFilters =
        Boolean(search) ||
        status !== 'all' ||
        source !== 'all' ||
        subscriberLocale !== 'all';

    const exportQuery = letterQuery({
        search,
        status: status === 'all' ? '' : status,
        source: source === 'all' ? '' : source,
        locale: subscriberLocale === 'all' ? '' : subscriberLocale,
        per_page: DEFAULT_PER_PAGE,
    });

    return (
        <>
            <Head title={t('Heritage Letter')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Heritage Letter')}
                    description={t(
                        'Bekijk nieuwsbriefabonnees en leveringsstatus.',
                    )}
                    icon={Mail}
                >
                    <Button variant="outline" asChild>
                        <a
                            href={
                                letter.export(uiLocale, {
                                    query: exportQuery,
                                }).url
                            }
                        >
                            <Download className="h-4 w-4" />
                            {t('Exporteren')}
                        </a>
                    </Button>
                </AdminPageHeader>
                {!letterConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            {t('Mailservice is niet gekoppeld')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'De abonnees hieronder zijn echt; Brevo-sync staat uit totdat de API-sleutel is geconfigureerd.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <div className="relative sm:col-span-2">
                        <Label htmlFor="letter-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="letter-search"
                            className="pl-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op e-mail of naam…')}
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
                            {STATUS_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={source} onValueChange={setSource}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Bron')}
                        >
                            <SelectValue placeholder={t('Alle bronnen')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle bronnen')}
                            </SelectItem>
                            {SOURCE_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={subscriberLocale}
                        onValueChange={setSubscriberLocale}
                    >
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Taal')}
                        >
                            <SelectValue placeholder={t('Alle talen')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle talen')}
                            </SelectItem>
                            {LOCALE_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
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
                            onClick={() => {
                                setSearch('');
                                setStatus('all');
                                setSource('all');
                                setSubscriberLocale('all');
                            }}
                            className="w-full sm:col-span-2 xl:col-span-5 xl:w-auto xl:justify-self-start"
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
                                <TableHead>{t('Abonnee')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    {t('Onderwerpen')}
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Bron')}
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    {t('Taal')}
                                </TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Ingeschreven op')}
                                </TableHead>
                                <TableHead className="hidden xl:table-cell">
                                    {t('Gesynchroniseerd')}
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
                                        {hasActiveFilters
                                            ? t('Geen abonnees gevonden.')
                                            : t('Nog geen abonnees.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((subscriber) => {
                                    const topics = preferenceBadges(
                                        subscriber.preferences,
                                        t,
                                    );

                                    return (
                                    <TableRow key={subscriber.id}>
                                        <TableCell>
                                            <span className="font-medium">
                                                {subscriber.name}
                                            </span>
                                            <p className="text-xs text-muted-foreground">
                                                {subscriber.email}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {translateSubscriberStatus(
                                                    subscriber.status,
                                                    t,
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            <div className="flex flex-wrap gap-1">
                                                {topics.length === 0
                                                    ? '—'
                                                    : topics.map((badge) => (
                                                          <Badge
                                                              key={badge.key}
                                                              variant="outline"
                                                          >
                                                              {badge.label}
                                                          </Badge>
                                                      ))}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {translateSubscriberSource(
                                                subscriber.source,
                                                t,
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden uppercase lg:table-cell">
                                            {subscriber.locale}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {subscriber.joined_at
                                                ? new Date(
                                                      subscriber.joined_at,
                                                  ).toLocaleDateString()
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="hidden text-muted-foreground xl:table-cell">
                                            {subscriber.synced_at
                                                ? new Date(
                                                      subscriber.synced_at,
                                                  ).toLocaleString()
                                                : '—'}
                                        </TableCell>
                                    </TableRow>
                                    );
                                })
                            )}
                        </TableBody>
                    </Table>
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

LetterIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Heritage Letter', href: letter.index(wayfinderLocale()) },
    ],
};
