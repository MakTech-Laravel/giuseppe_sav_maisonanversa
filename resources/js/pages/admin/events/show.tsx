import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Eye, Pencil, Users } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { EventTranslationsDialog } from '@/components/admin/event-translations-dialog';
import { Button } from '@/components/ui/button';
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
import customers from '@/routes/admin/customers';
import eventsRoutes from '@/routes/admin/events';

interface EventBooking {
    id: string;
    user_id: string;
    name: string;
    email: string;
    booked_at: string | null;
}

type EventLifecycleStatus = 'opening' | 'ongoing' | 'closed';

type LocaleCopy = {
    title: string;
    description: string;
    location: string;
};

type TranslationStatus = {
    title: boolean;
    description: boolean;
    location: boolean;
};

interface EventDetail {
    id: string;
    title: string;
    description?: string | null;
    starts_at: string;
    location: string;
    capacity: number | null;
    rsvp_count: number;
    thumbnail_url: string | null;
    status: EventLifecycleStatus;
    bookings: EventBooking[];
}

function customerShowUrl(userId: string) {
    return customers.show({
        locale: wayfinderLocale(),
        user: Number(userId),
    });
}

function formatStartsAt(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}

function formatBookedAt(value: string | null): string {
    if (!value) {
        return '—';
    }

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

export default function EventShow({
    event,
    locales,
    translations,
    translationStatus,
}: {
    event: EventDetail;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}) {
    const { t } = useTranslation();

    function updateEventStatus(nextStatus: EventLifecycleStatus) {
        router.patch(
            eventsRoutes.status({
                locale: wayfinderLocale(),
                event: Number(event.id),
            }).url,
            { status: nextStatus },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={event.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={event.title}
                    description={event.description ?? undefined}
                    icon={CalendarDays}
                >
                    <Button variant="outline" asChild>
                        <Link href={eventsRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar evenementen')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={eventsRoutes.edit({
                                locale: wayfinderLocale(),
                                event: Number(event.id),
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
                    <div className="w-full shrink-0 space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm lg:w-80">
                        {event.thumbnail_url ? (
                            <img
                                src={event.thumbnail_url}
                                alt={event.title}
                                className="aspect-video w-full rounded-lg object-cover"
                            />
                        ) : (
                            <div className="flex aspect-video items-center justify-center rounded-lg bg-muted text-xs text-muted-foreground">
                                {t('Geen thumbnail')}
                            </div>
                        )}
                        <div className="flex flex-col gap-2">
                            <EventTranslationsDialog
                                eventId={event.id}
                                locales={locales}
                                translations={translations}
                                translationStatus={translationStatus}
                            />
                        </div>
                        <dl className="space-y-4">
                            <Detail label={t('Referentie')} value={event.id} />
                            <div>
                                <dt className="text-muted-foreground">
                                    {t('Status')}
                                </dt>
                                <dd className="mt-1">
                                    <Select
                                        value={event.status}
                                        onValueChange={(value) =>
                                            updateEventStatus(
                                                value as EventLifecycleStatus,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            className="w-full"
                                            aria-label={t('Status wijzigen')}
                                        >
                                            <SelectValue>
                                                {statusLabel(event.status, t)}
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
                                </dd>
                            </div>
                            <Detail
                                label={t('Datum')}
                                value={formatStartsAt(event.starts_at)}
                            />
                            <Detail
                                label={t('Locatie')}
                                value={event.location}
                            />
                            <Detail
                                label={t('Aanwezigen')}
                                value={
                                    event.capacity != null
                                        ? `${event.rsvp_count} / ${event.capacity}`
                                        : String(event.rsvp_count)
                                }
                            />
                        </dl>
                    </div>

                    <div className="min-w-0 flex-1 overflow-hidden rounded-xl border bg-card shadow-sm">
                        <div className="flex items-center gap-2 border-b px-6 py-4">
                            <Users className="h-4 w-4 text-muted-foreground" />
                            <h2 className="text-sm font-semibold">
                                {t('Boekingen')}
                            </h2>
                            <span className="text-xs text-muted-foreground">
                                ({event.bookings.length})
                            </span>
                        </div>
                        {event.bookings.length === 0 ? (
                            <p className="px-6 py-10 text-sm text-muted-foreground">
                                {t('Nog niemand heeft geboekt.')}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50 hover:bg-muted/50">
                                        <TableHead>{t('Naam')}</TableHead>
                                        <TableHead className="hidden sm:table-cell">
                                            {t('E-mail')}
                                        </TableHead>
                                        <TableHead className="hidden md:table-cell">
                                            {t('Geboekt op')}
                                        </TableHead>
                                        <TableHead className="w-12 text-right">
                                            <span className="sr-only">
                                                {t('Acties')}
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {event.bookings.map((booking) => {
                                        const href = customerShowUrl(
                                            booking.user_id,
                                        );

                                        const openCustomer = () => {
                                            router.visit(href);
                                        };

                                        const onRowKeyDown = (
                                            keyboardEvent: KeyboardEvent<HTMLTableRowElement>,
                                        ) => {
                                            if (
                                                keyboardEvent.key === 'Enter' ||
                                                keyboardEvent.key === ' '
                                            ) {
                                                keyboardEvent.preventDefault();
                                                openCustomer();
                                            }
                                        };

                                        return (
                                            <TableRow
                                                key={booking.id}
                                                className="cursor-pointer"
                                                tabIndex={0}
                                                role="link"
                                                aria-label={t(
                                                    'Bekijk klant {{name}}',
                                                    { name: booking.name },
                                                )}
                                                onClick={openCustomer}
                                                onKeyDown={onRowKeyDown}
                                            >
                                                <TableCell className="font-medium">
                                                    {booking.name}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">
                                                    {booking.email}
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell">
                                                    {formatBookedAt(
                                                        booking.booked_at,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={(clickEvent) =>
                                                            clickEvent.stopPropagation()
                                                        }
                                                    >
                                                        <Link
                                                            href={href}
                                                            title={t(
                                                                'Bekijken',
                                                            )}
                                                            aria-label={t(
                                                                'Bekijken',
                                                            )}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium">{value}</dd>
        </div>
    );
}

EventShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Evenementen', href: eventsRoutes.index(wayfinderLocale()) },
        { title: 'Gegevens', href: eventsRoutes.index(wayfinderLocale()) },
    ],
};
