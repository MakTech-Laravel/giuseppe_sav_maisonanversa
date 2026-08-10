import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { ContactBureau } from '@/components/maison/contact/contact-bureau';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function Contact() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead page="contact" />

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
