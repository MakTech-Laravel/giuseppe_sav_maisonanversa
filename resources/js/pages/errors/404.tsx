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
                className="flex min-h-[calc(100vh-var(--topbar-h)-var(--nav-h))] flex-col justify-center pb-24"
                eyebrow="404"
                title={t('Pagina niet gevonden')}
                subtitle={t(
                    'De gevraagde pagina bestaat niet of is verplaatst. Gebruik de links hieronder om verder te gaan.',
                )}
            >
                <div className="flex flex-col items-center gap-4">
                    <MaisonButton as={MaisonLink} to="home" variant="hero">
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
                </div>
            </PageHero>
        </>
    );
}
