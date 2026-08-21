import { Head, Link } from '@inertiajs/react';
import { MapPin, Pencil, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';

interface PartnerClubRow {
    id: string;
    city: string;
    country: string;
    status: string;
    sort_order: number;
    is_published: boolean;
}

export default function PartnerClubsIndex({ clubs }: { clubs: PartnerClubRow[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t('Partner Clubs')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('Partner Clubs')} description={t('Beheer partnerclubs voor Club Corner.')} icon={MapPin}>
                    <Button asChild>
                        <Link href={`/${locale}/admin/partner-clubs/create`}>
                            <Plus className="h-4 w-4" /> {t('Club toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Stad')}</TableHead>
                                <TableHead>{t('Land')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Volgorde')}</TableHead>
                                <TableHead className="text-right">{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {clubs.map((club) => (
                                <TableRow key={club.id}>
                                    <TableCell className="font-medium">{club.city}</TableCell>
                                    <TableCell>{club.country}</TableCell>
                                    <TableCell>{club.status}</TableCell>
                                    <TableCell>{club.sort_order}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link href={`/${locale}/admin/partner-clubs/${club.id}/edit`} title={t('Bewerken')}>
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

PartnerClubsIndex.layout = {
    title: 'Partner Clubs',
};
