import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Pencil } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ProductFormFields } from '@/components/admin/product-form-fields';
import type { ProductFormData } from '@/components/admin/product-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';

interface CatalogProduct extends ProductFormData {
    id: number;
    edition_total: string | number | null;
    stock_quantity: string | number | null;
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
            archive_edition_numbers: product.archive_edition_numbers ?? '',
            stock_quantity: String(product.stock_quantity ?? ''),
            is_published: product.is_published,
            grants_founding_circle: product.grants_founding_circle,
            expected_delivery_label: product.expected_delivery_label ?? '',
        },
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
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
                            <ArrowLeft className="h-4 w-4" /> {t('Terug naar catalogus')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form
                    onSubmit={submit}
                    className="w-full max-w-2xl space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <ProductFormFields
                        data={form.data}
                        errors={form.errors}
                        setData={form.setData}
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
        { title: 'Catalogus', href: products.index(wayfinderLocale()) },
        { title: 'Bewerken', href: products.index(wayfinderLocale()) },
    ],
};
