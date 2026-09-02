import { Head, Link, router } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { PartnerClubTranslationsDialog } from '@/components/admin/partner-club-translations-dialog';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import partnerClubs from '@/routes/admin/partner-clubs';

interface PartnerClubRow {
    id: string;
    city: string;
    country: string;
    status: string;
    sort_order: number;
    is_published: boolean;
}

type LocaleCopy = {
    city: string;
    country: string;
};

type TranslationStatus = {
    city: boolean;
    country: boolean;
};

export default function PartnerClubsIndex({
    clubs,
    locales,
    translations,
    translationStatus,
}: {
    clubs: PartnerClubRow[];
    locales: string[];
    translations: Record<string, Record<string, LocaleCopy>>;
    translationStatus: Record<string, Record<string, TranslationStatus>>;
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t('Partner Clubs')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Partner Clubs')}
                    description={t('Beheer partnerclubs voor Club Corner.')}
                    icon={MapPin}
                >
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
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {clubs.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen partnerclubs gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                clubs.map((club) => (
                                    <TableRow key={club.id}>
                                        <TableCell className="font-medium">
                                            {club.city}
                                        </TableCell>
                                        <TableCell>{club.country}</TableCell>
                                        <TableCell>{club.status}</TableCell>
                                        <TableCell>{club.sort_order}</TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <PartnerClubTranslationsDialog
                                                partnerClubId={club.id}
                                                locales={locales}
                                                translations={
                                                    translations[club.id] ?? {}
                                                }
                                                translationStatus={
                                                    translationStatus[
                                                        club.id
                                                    ] ?? {}
                                                }
                                            />
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={partnerClubs.edit({
                                                        locale,
                                                        partnerClub: Number(
                                                            club.id,
                                                        ),
                                                    })}
                                                    title={t('Bewerken')}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    {t('Bewerken')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'Deze partner club wordt permanent verwijderd.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                                        partnerClubs.destroy({
                                                            locale,
                                                            partnerClub: Number(
                                                                club.id,
                                                            ),
                                                        }).url,
                                                    )
                                                }
                                            >
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    {t('Verwijderen')}
                                                </Button>
                                            </ConfirmDeleteDialog>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

PartnerClubsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Partner Clubs',
            href: partnerClubs.index(wayfinderLocale()),
        },
    ],
};
