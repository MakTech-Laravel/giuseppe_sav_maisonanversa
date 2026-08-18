import { Head, Link, router } from '@inertiajs/react';
import { Eye, MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
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
import courtsRoutes from '@/routes/admin/courts';

interface CourtRow {
    id: string;
    title: string;
    location: string;
    sort_order: number;
    is_published: boolean;
}

export default function CourtsIndex({ courts }: { courts: CourtRow[] }) {
    const { t } = useTranslation();

    function destroyCourt(courtId: string) {
        if (!window.confirm(t('Club Corner verwijderen?'))) {
            return;
        }

        router.delete(
            courtsRoutes.destroy({
                locale: wayfinderLocale(),
                court: courtId,
            }).url,
        );
    }

    return (
        <>
            <Head title={t('Club Corners')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Club Corners')}
                    description={t(
                        'Beheer gepubliceerde Club Corners op de communitykaart.',
                    )}
                    icon={MapPin}
                >
                    <Button asChild>
                        <Link href={courtsRoutes.create(wayfinderLocale())}>
                            <Plus className="h-4 w-4" /> {t('Corner toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Titel')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Locatie')}
                                </TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {courts.map((court) => (
                                <TableRow key={court.id}>
                                    <TableCell className="font-medium">
                                        {court.title}
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {court.location}
                                    </TableCell>
                                    <TableCell>
                                        {court.is_published
                                            ? t('Gepubliceerd')
                                            : t('Concept')}
                                    </TableCell>
                                    <TableCell className="space-x-1 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={courtsRoutes.show({
                                                    locale: wayfinderLocale(),
                                                    court: court.id,
                                                })}
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
                                                href={courtsRoutes.edit({
                                                    locale: wayfinderLocale(),
                                                    court: court.id,
                                                })}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            type="button"
                                            onClick={() =>
                                                destroyCourt(court.id)
                                            }
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

CourtsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Club Corners', href: courtsRoutes.index(wayfinderLocale()) },
    ],
};
