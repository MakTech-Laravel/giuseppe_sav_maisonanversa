import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function NewsletterUnsubscribed() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Uitgeschreven')}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
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
