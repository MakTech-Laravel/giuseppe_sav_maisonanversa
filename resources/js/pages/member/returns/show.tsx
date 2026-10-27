import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
    MemberStatusPill,
} from '@/components/member/member-ui';

type ReturnPayload = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    ship_by: string | null;
    return_address: string;
    can_add_tracking: boolean;
    can_cancel: boolean;
    carrier: string | null;
    tracking_number: string | null;
    refund_amount?: string | null;
    resolved_at?: string | null;
    payment_method?: string | null;
    is_refunded?: boolean;
    show_ship_instructions?: boolean;
    order: { id: number; reference?: string; label?: string };
};

export default function MemberReturnShow({
    return: orderReturn,
}: {
    return: ReturnPayload;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const showShip = orderReturn.show_ship_instructions ?? true;
    const isRefunded = orderReturn.is_refunded ?? false;

    return (
        <>
            <Head title={orderReturn.number} />
            <div className="mb-6">
                <Link
                    href={`/${locale}/member/orders/${orderReturn.order.id}`}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline hover:text-cream"
                >
                    ← {t('Terug naar bestelling')}
                </Link>
            </div>

            <MemberPageHeader
                eyebrow={t('Retour')}
                title={orderReturn.number}
                description={orderReturn.order.reference}
            />

            <div className="mb-6">
                <MemberStatusPill tone="neutral">
                    {t(orderReturn.status_label)}
                </MemberStatusPill>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <MemberPanel>
                    {isRefunded ? (
                        <>
                            <MemberSectionTitle
                                title={t('Terugbetaling voltooid')}
                            />
                            <p className="text-sand">
                                {t(
                                    'Uw terugbetaling is verwerkt. Het bedrag verschijnt binnen enkele werkdagen op uw rekening.',
                                )}
                            </p>
                            <dl className="mt-4 space-y-2 font-sans text-sm">
                                {orderReturn.refund_amount ? (
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-sand">
                                            {t('Bedrag')}
                                        </dt>
                                        <dd className="text-cream">
                                            €{orderReturn.refund_amount}
                                        </dd>
                                    </div>
                                ) : null}
                                {orderReturn.resolved_at ? (
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-sand">
                                            {t('Datum')}
                                        </dt>
                                        <dd className="text-cream">
                                            {orderReturn.resolved_at}
                                        </dd>
                                    </div>
                                ) : null}
                                {orderReturn.payment_method ? (
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-sand">
                                            {t('Betaalmethode')}
                                        </dt>
                                        <dd className="text-cream">
                                            {t(orderReturn.payment_method)}
                                        </dd>
                                    </div>
                                ) : null}
                            </dl>
                        </>
                    ) : showShip ? (
                        <>
                            <MemberSectionTitle title={t('Instructies')} />
                            <p className="text-sand">
                                {t('Stuur het artikel binnen 14 dagen naar:')}
                            </p>
                            <p className="mt-2 whitespace-pre-line text-cream">
                                {orderReturn.return_address}
                            </p>
                            {orderReturn.ship_by ? (
                                <p className="mt-4 font-sans text-sm text-gold">
                                    {t('Versturen tot')} {orderReturn.ship_by}
                                </p>
                            ) : null}
                        </>
                    ) : (
                        <>
                            <MemberSectionTitle title={t('Status')} />
                            <p className="text-sand">
                                {t(orderReturn.status_label)}
                            </p>
                        </>
                    )}
                </MemberPanel>

                <MemberPanel>
                    <MemberSectionTitle
                        title={t('Trackingnummer toevoegen')}
                    />
                    {orderReturn.can_add_tracking ? (
                        <Form
                            action={`/${locale}/member/returns/${orderReturn.id}/tracking`}
                            method="post"
                            className="space-y-3"
                        >
                            <input type="hidden" name="_method" value="patch" />
                            <input
                                name="carrier"
                                placeholder={t('Vervoerder')}
                                defaultValue={orderReturn.carrier ?? ''}
                                required
                                className="w-full border border-gold/25 bg-choc px-3 py-3 font-sans text-sm text-cream"
                            />
                            <input
                                name="tracking_number"
                                placeholder={t('Trackingnummer')}
                                defaultValue={orderReturn.tracking_number ?? ''}
                                required
                                className="w-full border border-gold/25 bg-choc px-3 py-3 font-sans text-sm text-cream"
                            />
                            <button
                                type="submit"
                                className="inline-flex min-h-11 items-center justify-center border border-cream px-5 font-sans text-[10px] tracking-[0.2em] text-cream uppercase"
                            >
                                {t('Trackingnummer toevoegen')}
                            </button>
                        </Form>
                    ) : (
                        <p className="text-sand">
                            {[orderReturn.carrier, orderReturn.tracking_number]
                                .filter(Boolean)
                                .join(' · ') || t('Nog geen tracking')}
                        </p>
                    )}

                    {orderReturn.can_cancel ? (
                        <button
                            type="button"
                            className="mt-6 font-sans text-[10px] tracking-[0.18em] text-sand uppercase underline"
                            onClick={() =>
                                router.delete(
                                    `/${locale}/member/returns/${orderReturn.id}`,
                                )
                            }
                        >
                            {t('Retour annuleren')}
                        </button>
                    ) : null}
                </MemberPanel>
            </div>
        </>
    );
}
