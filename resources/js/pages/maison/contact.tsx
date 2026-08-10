import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ContactBureau } from '@/components/maison/contact/contact-bureau';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function Contact() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Contact')} />

            <PageHero
                eyebrow={t('Bureau')}
                title={
                    <>
                        {t('Hoe kunnen wij u')} <em>{t('helpen?')}</em>
                    </>
                }
                subtitle={t(
                    'Welkom bij Maison Anversa. Selecteer een van de onderstaande opties — wij antwoorden altijd persoonlijk, binnen 24 uur op werkdagen.',
                )}
            />

            <ContactBureau />
        </>
    );
}
