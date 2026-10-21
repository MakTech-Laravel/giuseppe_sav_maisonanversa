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
    order: { id: number; reference?: string; label?: string };
};

export default function MemberReturnShow({
    return: orderReturn,
}: {
    return: ReturnPayload;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

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
                    <MemberSectionTitle title={t('Instructies')} />
                    <p className="text-sand">
                        {t('Stuur het artikel binnen 14 dagen naar:')}
                    </p>
                    <p className="mt-2 text-cream">{orderReturn.return_address}</p>
                    {orderReturn.ship_by ? (
                        <p className="mt-4 font-sans text-sm text-gold">
                            {t('Versturen tot')} {orderReturn.ship_by}
                        </p>
                    ) : null}
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
