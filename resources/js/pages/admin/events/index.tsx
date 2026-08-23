import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
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

interface EventRow {
    id: string;
    title: string;
    starts_at: string;
    location: string;
    capacity: number | null;
    rsvp_count: number;
    thumbnail_url: string | null;
}

function formatStartsAt(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}

export default function EventsIndex({ events }: { events: EventRow[] }) {
    const { t } = useTranslation();

    function destroyEvent(eventId: string) {
        if (!window.confirm(t('Evenement verwijderen?'))) {
            return;
        }

        router.delete(
            eventsRoutes.destroy({
                locale: wayfinderLocale(),
                event: eventId,
            }).url,
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
                            <Plus className="h-4 w-4" /> {t('Evenement toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="w-14" />
                                <TableHead>{t('Evenement')}</TableHead>
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
                            {events.map((event) => (
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
                                                    event: event.id,
                                                })}
                                                title={t('Boekingen bekijken')}
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
                                                    event: event.id,
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
                            ))}
                        </TableBody>
                    </Table>
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
