import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl } from '@/lib/maison-navigation';

export default function CheckoutCancel() {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Payment')}
                title={
                    <>
                        {t('Payment')} <em>{t('cancelled')}</em>
                    </>
                }
                subtitle={t(
                    'No worries — nothing was charged. You can restart your reservation when you are ready.',
                )}
            />

            <section className="mx-auto max-w-xl px-6 pb-24 text-center">
                <MaisonButton
                    as={MaisonLink}
                    href={foundingProductUrl(locale)}
                    variant="choc"
                >
                    {t('Back to the product')}
                </MaisonButton>
            </section>
        </>
    );
}
