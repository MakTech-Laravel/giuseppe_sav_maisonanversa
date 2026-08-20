import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';

interface SessionRow {
    id: string;
    host: string;
    location: string;
    starts_at: string;
    capacity: number | null;
    participants_count: number;
}

export default function CommunitySessionsIndex({ sessions }: { sessions: SessionRow[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t('Sessies')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('Sessies')} description={t('Beheer community-sessies en hosts.')} icon={CalendarDays}>
                    <Button asChild>
                        <Link href={`/${locale}/admin/sessions/create`}>
                            <Plus className="h-4 w-4" /> {t('Sessie toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Host')}</TableHead>
                                <TableHead>{t('Locatie')}</TableHead>
                                <TableHead>{t('Datum')}</TableHead>
                                <TableHead>{t('Capaciteit')}</TableHead>
                                <TableHead className="text-right">{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sessions.map((session) => (
                                <TableRow key={session.id}>
                                    <TableCell className="font-medium">{session.host}</TableCell>
                                    <TableCell>{session.location}</TableCell>
                                    <TableCell>{new Date(session.starts_at).toLocaleString()}</TableCell>
                                    <TableCell>{session.participants_count}{session.capacity != null ? ` / ${session.capacity}` : ''}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link href={`/${locale}/admin/sessions/${session.id}/edit`} title={t('Bewerken')}>
                                                <Pencil className="h-4 w-4" />
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

CommunitySessionsIndex.layout = {
    title: 'Sessies',
};
