import { Head, Link, router } from '@inertiajs/react';
import { DoorOpen, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { dashboard } from '@/routes/admin';
import openingOffers from '@/routes/admin/opening-offers';

interface OpeningOfferListItem {
    id: string;
    product: { id: number; name: string; slug: string };
    is_active: boolean;
    /** False when the product is unpublished or no longer active: the opening shows no register. */
    offerable: boolean;
    sort_order: number;
    total: number;
    available: number;
}

export default function OpeningOffersIndex({
    offers,
}: {
    offers: OpeningOfferListItem[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={t('Opening offer')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Opening offer')}
                    description={t(
                        'Choose which product the opening offers for reservation.',
                    )}
                    icon={DoorOpen}
                >
                    <Button asChild>
                        <Link href={openingOffers.create(locale)}>
                            <Plus className="h-4 w-4" /> {t('Add offer')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Product')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Numbers')}</TableHead>
                                <TableHead>{t('Order')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Actions')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {offers.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t(
                                            'No opening offer yet. The opening then shows no numbers.',
                                        )}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                offers.map((offer) => (
                                    <TableRow key={offer.id}>
                                        <TableCell className="whitespace-normal">
                                            <div className="font-medium">
                                                {offer.product.name}
                                            </div>
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                {offer.product.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell className="space-y-1 whitespace-normal">
                                            <Badge
                                                variant={
                                                    offer.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {offer.is_active
                                                    ? t('Active')
                                                    : t('Inactive')}
                                            </Badge>
                                            {offer.is_active &&
                                            !offer.offerable ? (
                                                <p className="text-xs text-destructive">
                                                    {t(
                                                        'Not visible: the product is not published or not active.',
                                                    )}
                                                </p>
                                            ) : null}
                                        </TableCell>
                                        <TableCell>
                                            {t(
                                                '{{available}} of {{total}} available',
                                                {
                                                    available: offer.available,
                                                    total: offer.total,
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {offer.sort_order}
                                        </TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={openingOffers.edit({
                                                        locale,
                                                        openingOffer: Number(
                                                            offer.id,
                                                        ),
                                                    })}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'This offer will be removed. The product itself stays.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                                        openingOffers.destroy({
                                                            locale,
                                                            openingOffer:
                                                                Number(
                                                                    offer.id,
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
                                                    {t('Remove')}
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

OpeningOffersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Opening offer',
            href: openingOffers.index(wayfinderLocale()),
        },
    ],
};
