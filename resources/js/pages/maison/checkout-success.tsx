import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function CheckoutSuccess({
    paid,
    editionNumber,
    orderId,
    orderReference,
}: {
    paid: boolean;
    editionNumber?: string | null;
    orderId?: number | null;
    orderReference?: string | null;
}) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const numberLabel = editionNumber
        ? t('Nr. {{number}}', {
              number: String(editionNumber).padStart(3, '0'),
          })
        : null;
    const memberOrderHref =
        auth?.user && orderId
            ? `/${locale}/member/orders/${orderId}`
            : null;

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Betaling')}
                title={
                    paid ? (
                        <>
                            {t('Reservering')} <em>{t('bevestigd')}</em>
                        </>
                    ) : (
                        <>
                            {t('Betaling')} <em>{t('in behandeling')}</em>
                        </>
                    )
                }
                subtitle={
                    paid
                        ? t(
                              'Dank u. Uw bestelling is ontvangen. U ontvangt een bevestiging per e-mail.',
                          )
                        : t(
                              'Als uw betaling zojuist is afgerond, kan de bevestiging even op zich laten wachten. Controleer uw e-mail of neem contact op met het bureau.',
                          )
                }
            />

            <section className="mx-auto max-w-xl px-6 pb-24 text-center">
                {orderReference && (
                    <p className="mb-3 font-sans text-[11px] tracking-[0.18em] text-stone uppercase">
                        {t('Referentie')}: {orderReference}
                    </p>
                )}
                {numberLabel && (
                    <p className="mb-8 font-serif text-3xl text-choc">
                        {numberLabel}
                    </p>
                )}
                <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <MaisonButton as={MaisonLink} to="home" variant="choc">
                        {t('Terug naar huis')}
                    </MaisonButton>
                    {memberOrderHref && (
                        <a
                            href={memberOrderHref}
                            className="inline-flex min-h-11 items-center justify-center border border-choc px-6 font-sans text-[11px] tracking-[0.18em] text-choc uppercase no-underline transition-colors hover:bg-choc hover:text-cream"
                        >
                            {t('Bekijk bestelling')}
                        </a>
                    )}
                </div>
            </section>
        </>
    );
}
