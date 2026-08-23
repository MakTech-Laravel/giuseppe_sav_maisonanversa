import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Package, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import type { ExistingFile } from '@/components/file-upload';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';

interface ProductDetails {
    id: number;
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    amount: string;
    currency: string;
    edition_total: number | null;
    archive_edition_numbers: number[];
    stock_quantity: number | null;
    is_published: boolean;
    grants_founding_circle: boolean;
    expected_delivery_label: string | null;
    stripe_price_id: string | null;
    primary_image: ExistingFile | null;
    gallery_images: ExistingFile[];
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border bg-card p-5 shadow-sm">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="mt-2 break-words text-sm font-medium">{value}</p>
        </div>
    );
}

export default function ShowProduct({ product }: { product: ProductDetails }) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const archiveLabel =
        product.archive_edition_numbers.length > 0
            ? product.archive_edition_numbers.join(', ')
            : t('Geen');

    return (
        <>
            <Head title={product.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={product.name}
                    description={t(
                        'Bekijk kerngegevens van dit catalogusproduct.',
                    )}
                    icon={Package}
                >
                    <Button variant="outline" asChild>
                        <Link href={products.index(locale)}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar catalogus')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={products.edit({
                                locale,
                                product: product.id,
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">
                        {product.type === 'limited_edition'
                            ? t('Gelimiteerde editie')
                            : t('Eenvoudige voorraad')}
                    </Badge>
                    <Badge variant="secondary">
                        {product.is_published
                            ? t('Gepubliceerd')
                            : t('Concept')}
                    </Badge>
                    {product.grants_founding_circle && (
                        <Badge variant="secondary">
                            {t('Founding Circle')}
                        </Badge>
                    )}
                </div>

                {(product.primary_image ||
                    product.gallery_images.length > 0) && (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {product.primary_image ? (
                            <div className="overflow-hidden rounded-xl border bg-card">
                                <img
                                    src={product.primary_image.url}
                                    alt={product.name}
                                    className="aspect-square w-full object-cover"
                                />
                                <p className="px-3 py-2 text-xs text-muted-foreground">
                                    {t('Primaire afbeelding')}
                                </p>
                            </div>
                        ) : null}
                        {product.gallery_images.map((image) => (
                            <div
                                key={image.id}
                                className="overflow-hidden rounded-xl border bg-card"
                            >
                                <img
                                    src={image.url}
                                    alt={image.name ?? product.name}
                                    className="aspect-square w-full object-cover"
                                />
                            </div>
                        ))}
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Detail label={t('Naam')} value={product.name} />
                    <Detail label={t('Slug')} value={product.slug} />
                    <Detail
                        label={t('Bedrag')}
                        value={`€ ${product.amount} ${product.currency.toUpperCase()}`}
                    />
                    <Detail
                        label={t('Verwachte levering')}
                        value={product.expected_delivery_label ?? t('Geen')}
                    />
                    <Detail
                        label={t('Voorraad')}
                        value={
                            product.stock_quantity != null
                                ? String(product.stock_quantity)
                                : t('Geen')
                        }
                    />
                    <Detail
                        label={t('Editiegrootte')}
                        value={
                            product.edition_total != null
                                ? String(product.edition_total)
                                : t('Geen')
                        }
                    />
                    <Detail label={t('Archiefnummers')} value={archiveLabel} />
                    <Detail
                        label={t('Stripe price ID')}
                        value={product.stripe_price_id ?? t('Geen')}
                    />
                </div>
            </div>
        </>
    );
}

ShowProduct.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Catalogus', href: products.index(wayfinderLocale()) },
        { title: 'Bekijken', href: products.index(wayfinderLocale()) },
    ],
};
