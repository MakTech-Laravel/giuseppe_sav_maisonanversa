import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function CheckoutCancel() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Betaling')}
                title={
                    <>
                        {t('Betaling')} <em>{t('geannuleerd')}</em>
                    </>
                }
                subtitle={t(
                    'Geen zorgen — er is niets in rekening gebracht. U kunt uw reservering opnieuw starten wanneer u klaar bent.',
                )}
            />

            <section className="mx-auto max-w-xl px-6 pb-24 text-center">
                <MaisonButton as={MaisonLink} to="product" variant="choc">
                    {t('Terug naar het product')}
                </MaisonButton>
            </section>
        </>
    );
}
