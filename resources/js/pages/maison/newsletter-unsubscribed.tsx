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
                title={t('Unsubscribed')}
                subtitle={t(
                    'You will no longer receive the Heritage Letter. You can subscribe again at any time.',
                )}
            />
        </>
    );
}
