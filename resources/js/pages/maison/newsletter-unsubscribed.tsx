import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function NewsletterUnsubscribed() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />
            <PageHero
                eyebrow={t('Heritage Letter')}
                title={t('Uitgeschreven')}
                subtitle={t(
                    'U ontvangt geen Heritage Letter meer. U kunt zich altijd opnieuw inschrijven.',
                )}
            />
        </>
    );
}
