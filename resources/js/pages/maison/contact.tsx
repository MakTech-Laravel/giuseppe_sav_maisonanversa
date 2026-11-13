import { useTranslation } from 'react-i18next';
import { ContactBureau } from '@/components/maison/contact/contact-bureau';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function Contact() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow="Bureau"
                title={
                    <>
                        {t('How can we')} <em>{t('help?')}</em>
                    </>
                }
                subtitle={t(
                    'Welcome to Maison Anversa. Select one of the options below — we always respond personally, within 24 hours on business days.',
                )}
            />

            <ContactBureau />
        </>
    );
}
