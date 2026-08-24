import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Shirt, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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
import dressingItems from '@/routes/admin/dressing-items';

interface DressingItemRow {
    id: string;
    name: string;
    slug: string;
    category: string;
    status: string;
    sort_order: number;
    is_published: boolean;
    image_url: string | null;
}

export default function DressingItemsIndex({
    items,
}: {
    items: DressingItemRow[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    const statusLabel = (status: string) =>
        status === 'available' ? t('Beschikbaar') : t('Binnenkort');

    return (
        <>
            <Head title={t('Kleedkamer')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Kleedkamer')}
                    description={t(
                        'Beheer de items van de publieke Kleedkamer-catalogus.',
                    )}
                    icon={Shirt}
                >
                    <Button asChild>
                        <Link href={dressingItems.create(locale)}>
                            <Plus className="h-4 w-4" /> {t('Item toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="w-16" />
                                <TableHead>{t('Naam')}</TableHead>
                                <TableHead>{t('Categorie')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Publicatie')}</TableHead>
                                <TableHead>{t('Volgorde')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Nog geen items toegevoegd.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            {item.image_url ? (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="size-10 rounded-md object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                                    <Shirt className="h-4 w-4" />
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {item.name}
                                        </TableCell>
                                        <TableCell>{item.category}</TableCell>
                                        <TableCell>
                                            {statusLabel(item.status)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {item.is_published
                                                    ? t('Gepubliceerd')
                                                    : t('Concept')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{item.sort_order}</TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={dressingItems.show({
                                                        locale,
                                                        dressingItem: item.id,
                                                    })}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    {t('Bekijken')}
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={dressingItems.edit({
                                                        locale,
                                                        dressingItem: item.id,
                                                    })}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    {t('Bewerken')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'Dit item wordt permanent verwijderd.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                                        dressingItems.destroy({
                                                            locale,
                                                            dressingItem:
                                                                item.id,
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

DressingItemsIndex.layout = {
    title: 'Kleedkamer',
};
