import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Eye, TriangleAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import eventsRoutes from '@/routes/admin/events';

interface EventRow {
    id: string;
    title: string;
    date: string;
    location: string;
    capacity: string;
    attendees: string;
    status: string;
    status_key: string;
}

function translateEventStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        Upcoming: 'Aankomend',
        Full: 'Volzet',
        Past: 'Afgelopen',
    };

    return t(statusMap[status] ?? status);
}

export default function EventsIndex({
    events,
    eventsConnected,
}: {
    events: EventRow[];
    eventsConnected: boolean;
}) {
    const { t } = useTranslation();

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
                />
                {!eventsConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            {t('Evenementenservice is niet gekoppeld')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'Deze evenementen zijn demogegevens totdat de evenementenservice is geconfigureerd.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Evenement')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Datum')}
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    {t('Locatie')}
                                </TableHead>
                                <TableHead>{t('Aanwezigen')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {events.map((event) => (
                                <TableRow key={event.id}>
                                    <TableCell>
                                        <span className="font-medium">
                                            {event.title}
                                        </span>
                                        <p className="text-xs text-muted-foreground">
                                            {event.id}
                                        </p>
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {event.date}
                                    </TableCell>
                                    <TableCell className="hidden lg:table-cell">
                                        {event.location}
                                    </TableCell>
                                    <TableCell>
                                        {event.attendees} / {event.capacity}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {translateEventStatus(
                                                event.status,
                                                t,
                                            )}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
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
                                                title={t(
                                                    'Evenement bekijken',
                                                )}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
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
