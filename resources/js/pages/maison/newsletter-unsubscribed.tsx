import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function NewsletterUnsubscribed() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />
            <PageHero
                eyebrow={t('Heritage Letter')}
                title={t('Uitgeschreven')}
                subtitle={t(
                    'Je ontvangt geen Heritage Letter meer. Je kunt zich altijd opnieuw inschrijven.',
                )}
            />
        </>
    );
}
