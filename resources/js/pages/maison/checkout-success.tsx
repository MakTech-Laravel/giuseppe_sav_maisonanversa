import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function CheckoutSuccess({
    paid,
    editionNumber,
}: {
    paid: boolean;
    editionNumber?: string | null;
    orderId?: number | null;
}) {
    const { t } = useTranslation();
    const numberLabel = editionNumber
        ? `No.${String(editionNumber).padStart(3, '0')}`
        : null;

    return (
        <>
            <MaisonSeoHead
                page="home"
                title={t('Betaling bevestigd — Maison Anversa')}
                description={t(
                    'Uw Founding Edition-reservering is ontvangen.',
                )}
                noIndex
            />

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
                              'Dank u. Uw Founding Edition-reservering is ontvangen. U ontvangt een bevestiging per e-mail.',
                          )
                        : t(
                              'Als uw betaling zojuist is afgerond, kan de bevestiging even op zich laten wachten. Controleer uw e-mail of neem contact op met het bureau.',
                          )
                }
            />

            <section className="mx-auto max-w-xl px-6 pb-24 text-center">
                {numberLabel && (
                    <p className="mb-8 font-serif text-3xl text-choc">
                        {numberLabel}
                    </p>
                )}
                <MaisonButton as={MaisonLink} to="home" variant="choc">
                    {t('Terug naar huis')}
                </MaisonButton>
            </section>
        </>
    );
}
