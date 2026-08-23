import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, PackagePlus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ProductFormFields } from '@/components/admin/product-form-fields';
import type { ProductFormData } from '@/components/admin/product-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';

const defaults: ProductFormData = {
    name: '',
    slug: '',
    type: 'simple',
    amount: '',
    edition_total: '100',
    edition_number_prefix: '',
    edition_number_postfix: '',
    archive_edition_numbers: [],
    stock_quantity: '0',
    is_published: true,
    grants_founding_circle: false,
    expected_delivery_label: '',
    primary_image: null,
    gallery_images: null,
    remove_primary_image: false,
    gallery_keep: [],
};

export default function CreateProduct() {
    const { t } = useTranslation();
    const form = useForm(products.store(wayfinderLocale()), defaults);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    return (
        <>
            <Head title={t('Product aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Product aanmaken')}
                    description={t('Voeg een catalogusproduct toe.')}
                    icon={PackagePlus}
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
                        isUploading={form.processing}
                        uploadProgress={form.progress?.percentage ?? null}
                        onCancelUpload={() => form.cancel()}
                    />
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Product aanmaken')}
                    </Button>
                </form>
            </div>
        </>
    );
}

CreateProduct.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Catalogus', href: products.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: products.create(wayfinderLocale()) },
    ],
};
