import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Pencil } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { FormStepper } from '@/components/admin/form-stepper';
import {
    ProductBasicsFields,
    ProductFaqFields,
    ProductMediaFields,
    ProductPricingFields,
    ProductPublishFields,
    ProductSectionFields,
    ProductSeoFields,
} from '@/components/admin/product-form-fields';
import type { ProductFormData } from '@/components/admin/product-form-fields';
import type { ExistingFile } from '@/components/file-upload';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { PRODUCT_FORM_STEPS } from '@/pages/admin/products/product-form-steps';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';
import type {
    ProductFaqFormData,
    ProductSectionCatalogueEntry,
    ProductSectionFormData,
} from '@/types/admin-product';
import { buildSectionForm } from '@/types/admin-product';

interface CatalogProduct {
    id: number;
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    status: 'active' | 'coming_soon' | 'archived';
    sort_order: number;
    amount: string;
    edition_total: string | number | null;
    edition_number_prefix?: string | null;
    edition_number_postfix?: string | null;
    archive_edition_numbers: number[];
    stock_quantity: string | number | null;
    is_published: boolean;
    public_at?: string | null;
    grants_founding_circle: boolean;
    expected_delivery_label: string | null;
    eyebrow?: string | null;
    hero_eyebrow?: string | null;
    hero_subtitle?: string | null;
    description?: string | null;
    meta_title?: string | null;
    meta_description?: string | null;
    meta_keywords?: string | null;
    primary_image: ExistingFile | null;
    og_image: ExistingFile | null;
    gallery_images: ExistingFile[];
    sections: ProductSectionFormData[];
    faqs: ProductFaqFormData[];
}

/** Product columns the details endpoint owns; media and content are separate. */
const DETAIL_FIELDS = [
    'name',
    'slug',
    'type',
    'status',
    'sort_order',
    'amount',
    'edition_total',
    'edition_number_prefix',
    'edition_number_postfix',
    'archive_edition_numbers',
    'stock_quantity',
    'is_published',
    'public_at',
    'grants_founding_circle',
    'expected_delivery_label',
    'eyebrow',
    'hero_eyebrow',
    'hero_subtitle',
    'description',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'og_image',
    'remove_og_image',
] as const satisfies readonly (keyof ProductFormData)[];

export default function EditProduct({
    product,
    sectionCatalogue,
}: {
    product: CatalogProduct;
    sectionCatalogue: ProductSectionCatalogueEntry[];
}) {
    const { t } = useTranslation();
    const [stepIndex, setStepIndex] = useState(0);
    const routeArgs = { locale: wayfinderLocale(), product: product.id };
    const step = PRODUCT_FORM_STEPS[stepIndex];

    /**
     * Typed explicitly (rather than via `satisfies`) so every field widens to
     * its declared type — `satisfies` only checks compatibility, it does not
     * stop e.g. `remove_primary_image: false` from being inferred as the
     * literal `false` instead of `boolean`, which then fails to structurally
     * match `ProductFormData` wherever `form.data` is passed around.
     */
    const initialData: ProductFormData = {
        name: product.name,
        slug: product.slug,
        type: product.type,
        status: product.status,
        sort_order: String(product.sort_order ?? 0),
        amount: product.amount,
        edition_total: String(product.edition_total ?? ''),
        edition_number_prefix: product.edition_number_prefix ?? '',
        edition_number_postfix: product.edition_number_postfix ?? '',
        archive_edition_numbers: product.archive_edition_numbers ?? [],
        stock_quantity: String(product.stock_quantity ?? ''),
        is_published: product.is_published,
        public_at: product.public_at ?? '',
        grants_founding_circle: product.grants_founding_circle,
        expected_delivery_label: product.expected_delivery_label ?? '',
        eyebrow: product.eyebrow ?? '',
        hero_eyebrow: product.hero_eyebrow ?? '',
        hero_subtitle: product.hero_subtitle ?? '',
        description: product.description ?? '',
        meta_title: product.meta_title ?? '',
        meta_description: product.meta_description ?? '',
        meta_keywords: product.meta_keywords ?? '',
        og_image: null,
        remove_og_image: false,
        primary_image: null,
        gallery_images: null,
        remove_primary_image: false,
        gallery_keep: (product.gallery_images ?? []).map((file) =>
            String(file.id),
        ),
        sections: buildSectionForm(sectionCatalogue, product.sections ?? []),
        faqs: product.faqs ?? [],
    };

    const form = useForm(products.update(routeArgs), initialData);

    /**
     * Each tab narrows the payload to the keys its endpoint owns, so saving one
     * tab can never overwrite what another tab holds. transform() is called as
     * its own statement because it does not return the form.
     */
    const saveDetails = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) =>
            Object.fromEntries(
                DETAIL_FIELDS.map((field) => [field, data[field]]),
            ),
        );
        form.submit(products.update(routeArgs), {
            preserveScroll: true,
            forceFormData:
                Boolean(form.data.og_image) || form.data.remove_og_image,
            onSuccess: () => {
                form.setData((current) => ({
                    ...current,
                    og_image: null,
                    remove_og_image: false,
                }));
            },
        });
    };

    const saveMedia = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            primary_image: data.primary_image,
            gallery_images: data.gallery_images,
            remove_primary_image: data.remove_primary_image,
            gallery_keep: data.gallery_keep,
        }));
        form.submit(products.media.update(routeArgs), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const saveSections = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ sections: data.sections }));
        form.submit(products.sections.update(routeArgs), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.setData((current) => ({
                    ...current,
                    sections: current.sections.map((section) => ({
                        ...section,
                        image: null,
                        remove_image: false,
                    })),
                }));
            },
        });
    };

    const saveFaqs = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ faqs: data.faqs }));
        form.submit(products.faqs.update(routeArgs), { preserveScroll: true });
    };

    const shared = {
        data: form.data,
        errors: form.errors,
        setData: form.setData,
    };

    const saveButton = (label: string) => (
        <Button type="submit" disabled={form.processing}>
            {form.processing && <Loader2 className="h-4 w-4 animate-spin" />}
            {t(label)}
        </Button>
    );

    return (
        <>
            <Head title={t('Product bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Product bewerken')}
                    description={t('Elke tab wordt afzonderlijk opgeslagen.')}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={products.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar product')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <FormStepper
                    steps={PRODUCT_FORM_STEPS}
                    currentIndex={stepIndex}
                    furthestIndex={PRODUCT_FORM_STEPS.length - 1}
                    onSelect={setStepIndex}
                    allowFreeNavigation
                />

                {step.id === 'basics' ? (
                    <form onSubmit={saveDetails} className="space-y-6">
                        <ProductBasicsFields {...shared} />
                        <ProductSeoFields
                            {...shared}
                            existingOgImage={product.og_image}
                        />
                        {saveButton('Wijzigingen opslaan')}
                    </form>
                ) : null}

                {step.id === 'pricing' ? (
                    <form onSubmit={saveDetails} className="space-y-6">
                        <ProductPricingFields {...shared} />
                        {saveButton('Wijzigingen opslaan')}
                    </form>
                ) : null}

                {step.id === 'media' ? (
                    <form onSubmit={saveMedia} className="space-y-6">
                        <ProductMediaFields
                            {...shared}
                            existingPrimary={product.primary_image}
                            existingGallery={product.gallery_images}
                            isUploading={form.processing}
                            uploadProgress={form.progress?.percentage ?? null}
                            onCancelUpload={() => form.cancel()}
                        />
                        {saveButton('Afbeeldingen opslaan')}
                    </form>
                ) : null}

                {step.id === 'sections' ? (
                    <form onSubmit={saveSections} className="space-y-6">
                        <ProductSectionFields
                            {...shared}
                            catalogue={sectionCatalogue}
                            storedSections={product.sections}
                        />
                        {saveButton('Secties opslaan')}
                    </form>
                ) : null}

                {step.id === 'faq' ? (
                    <form onSubmit={saveFaqs} className="space-y-6">
                        <ProductFaqFields {...shared} />
                        {saveButton('Vragen opslaan')}
                    </form>
                ) : null}

                {step.id === 'publish' ? (
                    <form onSubmit={saveDetails} className="space-y-6">
                        <ProductPublishFields {...shared} />
                        {saveButton('Publicatie opslaan')}
                    </form>
                ) : null}
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
