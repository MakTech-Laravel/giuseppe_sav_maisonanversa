import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function Error404() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead
                title={t('Pagina niet gevonden')}
                description={t(
                    'Deze pagina bestaat niet. Keer terug naar Maison Anversa.',
                )}
                noIndex
            />

            <PageHero
                eyebrow="404"
                title={t('Pagina niet gevonden')}
                subtitle={t(
                    'De gevraagde pagina bestaat niet of is verplaatst. Gebruik de links hieronder om verder te gaan.',
                )}
            />

            <section className="mx-auto flex max-w-xl flex-col items-center gap-4 px-6 pb-24 text-center">
                <MaisonButton as={MaisonLink} to="home" variant="choc">
                    {t('Terug naar huis')}
                </MaisonButton>
                <MaisonButton as={MaisonLink} to="product" variant="ghost">
                    {t('Ontdek Heritage No.001 →')}
                </MaisonButton>
                <MaisonButton as={MaisonLink} to="journal" variant="ghost">
                    {t('Verken het Journal →')}
                </MaisonButton>
                <MaisonButton as={MaisonLink} to="contact" variant="ghost">
                    {t('Contact')}
                </MaisonButton>
            </section>
        </>
    );
}
