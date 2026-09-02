import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BookText } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
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
import circleRoutes from '@/routes/admin/circle';

interface RegisterEntry {
    id: string;
    name: string;
    edition_number: string | null;
    joined_at: string | null;
    still_member: boolean;
}

export default function CircleRegister({
    entries,
}: {
    entries: RegisterEntry[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Naamregister')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Naamregister')}
                    description={t(
                        'Het permanente register van iedereen die ooit Founding Circle-lid is geworden. Namen blijven hier staan, ook na het verwijderen van de rol.',
                    )}
                    icon={BookText}
                >
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar Founding Circle')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Editie')}</TableHead>
                                <TableHead>{t('Naam')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Ingeschreven op')}
                                </TableHead>
                                <TableHead>{t('Status')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell className="font-medium">
                                        {entry.edition_number
                                            ? `№ ${entry.edition_number}`
                                            : '—'}
                                    </TableCell>
                                    <TableCell>{entry.name}</TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        {entry.joined_at ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                entry.still_member
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {entry.still_member
                                                ? t('Actief lid')
                                                : t('In het register')}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {entries.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="py-8 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Nog geen namen in het register.')}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

CircleRegister.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Founding Circle',
            href: circleRoutes.index(wayfinderLocale()),
        },
        {
            title: 'Naamregister',
            href: circleRoutes.register(wayfinderLocale()),
        },
    ],
};
