import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Shipping() {
    const { t } = useTranslation();
    const { legalPage } = usePage<{
        legalPage: { slug: string; body: string };
    }>().props;

    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout title={t('Verzending & Retour')} body={legalPage.body} />
        </>
    );
}
