import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, DoorOpen, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminResourceShell } from '@/components/admin/admin-resource-shell';
import {
    OpeningOfferFormAside,
    OpeningOfferFormFields,
} from '@/components/admin/opening-offer-form-fields';
import type { OpeningOfferProductOption } from '@/components/admin/opening-offer-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import openingOffers from '@/routes/admin/opening-offers';

interface OpeningOfferEditProps {
    offer: {
        id: string;
        product_id: number;
        is_active: boolean;
        sort_order: number;
    };
    products: OpeningOfferProductOption[];
}

export default function EditOpeningOffer({
    offer,
    products,
}: OpeningOfferEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const form = useForm(
        openingOffers.update({
            locale,
            openingOffer: Number(offer.id),
        }),
        {
            product_id: offer.product_id,
            is_active: offer.is_active,
            sort_order: offer.sort_order,
        },
    );

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            sort_order: Number(data.sort_order),
        }));
        form.submit();
    }

    return (
        <>
            <Head title={t('Aanbod bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Aanbod bewerken')}
                    description={
                        products.find(
                            (product) => product.id === offer.product_id,
                        )?.name
                    }
                    icon={DoorOpen}
                >
                    <Button variant="outline" asChild>
                        <Link href={openingOffers.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell aside={<OpeningOfferFormAside />}>
                        <OpeningOfferFormFields
                            data={form.data}
                            errors={form.errors}
                            products={products}
                            setData={form.setData}
                        />
                    </AdminResourceShell>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : null}
                            {t('Opslaan')}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={openingOffers.index(locale)}>
                                {t('Annuleren')}
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

EditOpeningOffer.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Opening-aanbod',
            href: openingOffers.index(wayfinderLocale()),
        },
        { title: 'Bewerken', href: '#' },
    ],
};
