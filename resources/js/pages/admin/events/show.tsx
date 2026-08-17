import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
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
import eventsRoutes from '@/routes/admin/events';

interface EventDetail {
    id: string;
    title: string;
    description?: string | null;
    starts_at: string;
    location: string;
    capacity: number | null;
    rsvp_count: number;
    guest_list: { name: string; email: string }[];
}

function formatStartsAt(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}

export default function EventShow({ event }: { event: EventDetail }) {
    const { t } = useTranslation();

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
                                event: event.id,
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="grid max-w-5xl gap-6 lg:grid-cols-3">
                    <dl className="space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                        <Detail label={t('Referentie')} value={event.id} />
                        <Detail
                            label={t('Datum')}
                            value={formatStartsAt(event.starts_at)}
                        />
                        <Detail label={t('Locatie')} value={event.location} />
                        <Detail
                            label={t('Aanwezigen')}
                            value={
                                event.capacity != null
                                    ? `${event.rsvp_count} / ${event.capacity}`
                                    : String(event.rsvp_count)
                            }
                        />
                    </dl>
                    <div className="overflow-hidden rounded-xl border bg-card shadow-sm lg:col-span-2">
                        <div className="border-b px-6 py-4">
                            <h2 className="text-sm font-semibold">
                                {t('Gastenlijst')}
                            </h2>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead>{t('Naam')}</TableHead>
                                    <TableHead className="hidden sm:table-cell">
                                        {t('E-mail')}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {event.guest_list.map((guest) => (
                                    <TableRow key={guest.email}>
                                        <TableCell className="font-medium">
                                            {guest.name}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {guest.email}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
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
