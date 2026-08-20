import { useTranslation } from 'react-i18next';
import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Privacy({
    legalPage,
}: {
    legalPage: { slug: string; body: string };
}) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout
                title={t('Privacybeleid')}
                body={`${legalPage.body}\n\n${t('Contacteer ons via')}: ${t('onze contactpagina')}`}
            />
            <div className="sr-only">
                <MaisonLink to="contact">{t('onze contactpagina')}</MaisonLink>
            </div>
        </>
    );
}
