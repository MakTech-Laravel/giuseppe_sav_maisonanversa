import { Form, Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';

type Props = {
    return: {
        id: number;
        number: string;
        status_label: string;
        ship_by: string | null;
        return_address: string;
        can_add_tracking: boolean;
        carrier: string | null;
        tracking_number: string | null;
        refund_amount?: string | null;
        resolved_at?: string | null;
        payment_method?: string | null;
        is_refunded?: boolean;
        show_ship_instructions?: boolean;
        order: { id: number; reference?: string };
        trackingUrl: string;
    };
};

export default function GuestReturnShow({
    return: orderReturn,
}: Props) {
    const { t } = useTranslation();
    const showShip = orderReturn.show_ship_instructions ?? true;
    const isRefunded = orderReturn.is_refunded ?? false;

    return (
        <>
            <Head title={orderReturn.number} />
            <PageHero
                eyebrow={t('Retour')}
                title={orderReturn.number}
                subtitle={orderReturn.order.reference}
            />

            <section className="bg-cream px-[6vw] py-16 text-choc">
                <div className="mx-auto grid max-w-4xl gap-8 lg:grid-cols-2">
                    <div className="border border-choc/15 bg-white px-6 py-6">
                        <p className="font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                            {t('Status')}
                        </p>
                        <p className="mt-2 font-medium">
                            {t(orderReturn.status_label)}
                        </p>

                        {isRefunded ? (
                            <>
                                <p className="mt-6 font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                                    {t('Terugbetaling voltooid')}
                                </p>
                                <p className="mt-3 text-[#5C4A3D]">
                                    {t(
                                        'Uw terugbetaling is verwerkt. Het bedrag verschijnt binnen enkele werkdagen op uw rekening.',
                                    )}
                                </p>
                                <dl className="mt-4 space-y-2 font-sans text-sm">
                                    {orderReturn.refund_amount ? (
                                        <div className="flex justify-between gap-4">
                                            <dt className="text-[#8A7363]">
                                                {t('Bedrag')}
                                            </dt>
                                            <dd>
                                                €{orderReturn.refund_amount}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {orderReturn.resolved_at ? (
                                        <div className="flex justify-between gap-4">
                                            <dt className="text-[#8A7363]">
                                                {t('Datum')}
                                            </dt>
                                            <dd>{orderReturn.resolved_at}</dd>
                                        </div>
                                    ) : null}
                                    {orderReturn.payment_method ? (
                                        <div className="flex justify-between gap-4">
                                            <dt className="text-[#8A7363]">
                                                {t('Betaalmethode')}
                                            </dt>
                                            <dd>
                                                {t(orderReturn.payment_method)}
                                            </dd>
                                        </div>
                                    ) : null}
                                </dl>
                            </>
                        ) : showShip ? (
                            <>
                                <p className="mt-6 font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                                    {t('Instructies')}
                                </p>
                                <p className="mt-3 text-[#5C4A3D]">
                                    {t(
                                        'Stuur het artikel binnen 14 dagen naar:',
                                    )}
                                </p>
                                <p className="mt-2 whitespace-pre-line text-choc">
                                    {orderReturn.return_address}
                                </p>
                                {orderReturn.ship_by ? (
                                    <p className="mt-4 font-sans text-sm text-[#8A6A3B]">
                                        {t('Versturen tot')}{' '}
                                        {orderReturn.ship_by}
                                    </p>
                                ) : null}
                            </>
                        ) : null}
                    </div>

                    <div className="border border-choc/15 bg-white px-6 py-6">
                        <p className="font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                            {t('Trackingnummer toevoegen')}
                        </p>
                        {orderReturn.can_add_tracking ? (
                            <Form
                                action={orderReturn.trackingUrl}
                                method="post"
                                className="mt-4 space-y-3"
                            >
                                <input
                                    type="hidden"
                                    name="_method"
                                    value="patch"
                                />
                                <input
                                    name="carrier"
                                    placeholder={t('Vervoerder')}
                                    defaultValue={orderReturn.carrier ?? ''}
                                    required
                                    className="w-full border border-choc/20 bg-cream px-3 py-3 font-sans text-sm"
                                />
                                <input
                                    name="tracking_number"
                                    placeholder={t('Trackingnummer')}
                                    defaultValue={
                                        orderReturn.tracking_number ?? ''
                                    }
                                    required
                                    className="w-full border border-choc/20 bg-cream px-3 py-3 font-sans text-sm"
                                />
                                <button
                                    type="submit"
                                    className="inline-flex min-h-11 items-center justify-center border border-choc px-5 font-sans text-[10px] tracking-[0.2em] text-choc uppercase"
                                >
                                    {t('Trackingnummer toevoegen')}
                                </button>
                            </Form>
                        ) : (
                            <p className="mt-4 text-[#5C4A3D]">
                                {[
                                    orderReturn.carrier,
                                    orderReturn.tracking_number,
                                ]
                                    .filter(Boolean)
                                    .join(' · ') || t('Nog geen tracking')}
                            </p>
                        )}
                    </div>
                </div>
            </section>
        </>
    );
}
