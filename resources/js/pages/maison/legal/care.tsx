import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Care() {
    const { t } = useTranslation();
    const { legalPage } = usePage<{
        legalPage: { slug: string; body: string };
    }>().props;

    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout title={t('Zorg & Garantie')} body={legalPage.body} />
            <div className="sr-only">
                <MaisonLink to="contact">{t('contactpagina')}</MaisonLink>
            </div>
        </>
    );
}
