import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Loader2, PackagePlus } from 'lucide-react';
import { useMemo, useState } from 'react';
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
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { PRODUCT_FORM_STEPS } from '@/pages/admin/products/product-form-steps';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';
import type { ProductSectionCatalogueEntry } from '@/types/admin-product';
import { buildSectionForm } from '@/types/admin-product';

/** Fields Precognition validates before each step may be left. */
const STEP_FIELDS: Record<string, (keyof ProductFormData)[]> = {
    basics: [
        'name',
        'slug',
        'type',
        'status',
        'sort_order',
        'eyebrow',
        'hero_eyebrow',
        'hero_subtitle',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ],
    pricing: [
        'amount',
        'expected_delivery_label',
        'grants_founding_circle',
        'edition_total',
        'edition_number_prefix',
        'edition_number_postfix',
        'archive_edition_numbers',
        'stock_quantity',
    ],
    media: [],
    sections: ['sections'],
    faq: ['faqs'],
    publish: ['is_published', 'public_at'],
};

export default function CreateProduct({
    sectionCatalogue,
}: {
    sectionCatalogue: ProductSectionCatalogueEntry[];
}) {
    const { t } = useTranslation();
    const [stepIndex, setStepIndex] = useState(0);
    const [furthestIndex, setFurthestIndex] = useState(0);

    const defaults: ProductFormData = useMemo(
        () => ({
            name: '',
            slug: '',
            type: 'simple',
            status: 'active',
            sort_order: '0',
            amount: '',
            edition_total: '100',
            edition_number_prefix: '',
            edition_number_postfix: '',
            archive_edition_numbers: [],
            stock_quantity: '0',
            is_published: true,
            public_at: '',
            grants_founding_circle: false,
            expected_delivery_label: '',
            eyebrow: '',
            hero_eyebrow: '',
            hero_subtitle: '',
            description: '',
            meta_title: '',
            meta_description: '',
            meta_keywords: '',
            og_image: null,
            remove_og_image: false,
            primary_image: null,
            gallery_images: null,
            remove_primary_image: false,
            gallery_keep: [],
            sections: buildSectionForm(sectionCatalogue),
            faqs: [],
        }),
        [sectionCatalogue],
    );

    const form = useForm(products.store(wayfinderLocale()), defaults);
    const step = PRODUCT_FORM_STEPS[stepIndex];
    const isLastStep = stepIndex === PRODUCT_FORM_STEPS.length - 1;

    /**
     * Gates forward navigation on the same server rules that guard the final
     * submit, scoped to the current step's fields. File inputs are excluded so
     * the validate-only request stays a plain JSON round trip.
     */
    const goNext = () => {
        const fields = STEP_FIELDS[step.id];

        if (fields.length === 0) {
            advance();

            return;
        }

        form.withoutFileValidation().validate({
            only: fields,
            onSuccess: advance,
        });
    };

    const advance = () => {
        const next = Math.min(stepIndex + 1, PRODUCT_FORM_STEPS.length - 1);
        setStepIndex(next);
        setFurthestIndex((current) => Math.max(current, next));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    const shared = {
        data: form.data,
        errors: form.errors,
        setData: form.setData,
    };

    return (
        <>
            <Head title={t('Product aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Product aanmaken')}
                    description={t(
                        'Voeg een catalogusproduct toe in zes stappen.',
                    )}
                    icon={PackagePlus}
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
                    furthestIndex={furthestIndex}
                    onSelect={setStepIndex}
                />

                <form onSubmit={submit} className="w-full space-y-6">
                    {step.id === 'basics' ? (
                        <>
                            <ProductBasicsFields {...shared} />
                            <ProductSeoFields {...shared} />
                        </>
                    ) : null}
                    {step.id === 'pricing' ? (
                        <ProductPricingFields {...shared} />
                    ) : null}
                    {step.id === 'media' ? (
                        <ProductMediaFields
                            {...shared}
                            isUploading={form.processing}
                            uploadProgress={form.progress?.percentage ?? null}
                            onCancelUpload={() => form.cancel()}
                        />
                    ) : null}
                    {step.id === 'sections' ? (
                        <ProductSectionFields
                            {...shared}
                            catalogue={sectionCatalogue}
                        />
                    ) : null}
                    {step.id === 'faq' ? (
                        <ProductFaqFields {...shared} />
                    ) : null}
                    {step.id === 'publish' ? (
                        <ProductPublishFields {...shared} />
                    ) : null}

                    <div className="flex items-center justify-between gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={stepIndex === 0}
                            onClick={() => setStepIndex(stepIndex - 1)}
                        >
                            <ArrowLeft className="h-4 w-4" />
                            {t('Vorige')}
                        </Button>

                        {isLastStep ? (
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                )}
                                {t('Product aanmaken')}
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                disabled={form.validating}
                                onClick={goNext}
                            >
                                {form.validating && (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                )}
                                {t('Volgende')}
                                <ArrowRight className="h-4 w-4" />
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}

CreateProduct.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Product', href: products.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: products.create(wayfinderLocale()) },
    ],
};
