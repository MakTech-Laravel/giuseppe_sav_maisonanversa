import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Pencil } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ProductFormFields } from '@/components/admin/product-form-fields';
import type { ProductFormData } from '@/components/admin/product-form-fields';
import type { ExistingFile } from '@/components/file-upload';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';

interface CatalogProduct {
    id: number;
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    amount: string;
    edition_total: string | number | null;
    edition_number_prefix?: string | null;
    edition_number_postfix?: string | null;
    archive_edition_numbers: number[];
    stock_quantity: string | number | null;
    is_published: boolean;
    grants_founding_circle: boolean;
    expected_delivery_label: string | null;
    eyebrow?: string | null;
    hero_eyebrow?: string | null;
    hero_subtitle?: string | null;
    description?: string | null;
    primary_image: ExistingFile | null;
    gallery_images: ExistingFile[];
}

export default function EditProduct({ product }: { product: CatalogProduct }) {
    const { t } = useTranslation();
    const form = useForm(
        products.update({ locale: wayfinderLocale(), product: product.id }),
        {
            name: product.name,
            slug: product.slug,
            type: product.type,
            amount: product.amount,
            edition_total: String(product.edition_total ?? ''),
            edition_number_prefix: product.edition_number_prefix ?? '',
            edition_number_postfix: product.edition_number_postfix ?? '',
            archive_edition_numbers: product.archive_edition_numbers ?? [],
            stock_quantity: String(product.stock_quantity ?? ''),
            is_published: product.is_published,
            grants_founding_circle: product.grants_founding_circle,
            expected_delivery_label: product.expected_delivery_label ?? '',
            eyebrow: product.eyebrow ?? '',
            hero_eyebrow: product.hero_eyebrow ?? '',
            hero_subtitle: product.hero_subtitle ?? '',
            description: product.description ?? '',
            primary_image: null as File | null,
            gallery_images: null as File[] | null,
            remove_primary_image: false,
            gallery_keep: (product.gallery_images ?? []).map((file) =>
                String(file.id),
            ),
        } satisfies ProductFormData,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    return (
        <>
            <Head title={t('Product bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Product bewerken')}
                    description={t('Werk catalogus- en voorraadvelden bij.')}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={products.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar catalogus')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form onSubmit={submit} className="w-full space-y-6">
                    <ProductFormFields
                        data={form.data}
                        errors={form.errors}
                        setData={form.setData}
                        existingPrimary={product.primary_image}
                        existingGallery={product.gallery_images}
                        isUploading={form.processing}
                        uploadProgress={form.progress?.percentage ?? null}
                        onCancelUpload={() => form.cancel()}
                    />
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Wijzigingen opslaan')}
                    </Button>
                </form>
            </div>
        </>
    );
}

EditProduct.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Product', href: products.index(wayfinderLocale()) },
        { title: 'Bewerken', href: products.index(wayfinderLocale()) },
    ],
};
