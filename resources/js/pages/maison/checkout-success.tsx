import { usePage, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function CheckoutSuccess({
    paid,
    pendingConfirmation = false,
    editionNumber,
    orderId,
    orderReference,
}: {
    paid: boolean;
    pendingConfirmation?: boolean;
    editionNumber?: string | null;
    orderId?: number | null;
    orderReference?: string | null;
}) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const { stop } = usePoll(
        2000,
        {
            only: [
                'paid',
                'pendingConfirmation',
                'editionNumber',
                'orderId',
                'orderReference',
            ],
        },
        {
            autoStart: pendingConfirmation,
        },
    );
    const numberLabel = editionNumber
        ? t('Nº {{number}}', {
              number: String(editionNumber).padStart(3, '0'),
          })
        : null;
    const memberOrderHref =
        auth?.user && orderId ? `/${locale}/member/orders/${orderId}` : null;

    useEffect(() => {
        if (paid) {
            stop();
        }
    }, [paid, stop]);

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Payment')}
                title={
                    paid ? (
                        <>
                            {t('Reservation')} <em>{t('confirmed')}</em>
                        </>
                    ) : (
                        <>
                            {t('Payment')} <em>{t('pending')}</em>
                        </>
                    )
                }
                subtitle={
                    paid
                        ? t(
                              'Thank you. Your order has been received. You will receive a confirmation by email.',
                          )
                        : t(
                              'If your payment has just completed, confirmation may take a moment. Check your email or contact the house.',
                          )
                }
            />

            <section className="mx-auto max-w-xl px-6 pb-24 text-center">
                {orderReference && (
                    <p className="mb-3 font-sans text-[11px] tracking-[0.18em] text-stone uppercase">
                        {t('Reference')}: {orderReference}
                    </p>
                )}
                {numberLabel && (
                    <p className="mb-8 font-serif text-3xl text-choc">
                        {numberLabel}
                    </p>
                )}
                <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <MaisonButton as={MaisonLink} to="home" variant="choc">
                        {t('Back home')}
                    </MaisonButton>
                    {memberOrderHref && (
                        <a
                            href={memberOrderHref}
                            className="inline-flex min-h-11 items-center justify-center border border-choc px-6 font-sans text-[11px] tracking-[0.18em] text-choc uppercase no-underline transition-colors hover:bg-choc hover:text-cream"
                        >
                            {t('View order')}
                        </a>
                    )}
                </div>
            </section>
        </>
    );
}
