import { usePage } from '@inertiajs/react';
import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Shipping() {
    const { legalPage } = usePage<{
        legalPage: { slug: string; body: string };
    }>().props;

    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout
                title="Verzending & Retour"
                body={legalPage.body}
            />
        </>
    );
}
