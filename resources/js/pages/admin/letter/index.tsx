import { Head } from '@inertiajs/react';
import { Mail, TriangleAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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

interface Subscriber {
    email: string;
    name: string;
    status: string;
    joined_at: string;
}

function translateSubscriberStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        Active: 'Actief',
        Inactive: 'Inactief',
        Unsubscribed: 'Uitgeschreven',
    };

    return t(statusMap[status] ?? status);
}

export default function LetterIndex({
    subscribers,
    letterConnected,
}: {
    subscribers: Subscriber[];
    letterConnected: boolean;
}) {
    const { t } = useTranslation();

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
                />
                {!letterConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            {t('Mailservice is niet gekoppeld')}
                        </AlertTitle>
                        <AlertDescription>
                            {t('De abonnees hieronder zijn demogegevens.')}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Abonnee')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Ingeschreven op')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {subscribers.map((subscriber) => (
                                <TableRow key={subscriber.email}>
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
                                    <TableCell className="hidden sm:table-cell">
                                        {new Date(
                                            subscriber.joined_at,
                                        ).toLocaleDateString()}
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

LetterIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Heritage Letter', href: letter.index(wayfinderLocale()) },
    ],
};
