import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { LegalPageLinks } from '@/components/maison/legal/legal-page-links';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Terms({
    legalPage,
}: {
    legalPage: { slug: string; body: string };
}) {
    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout title="Terms & Conditions" body={legalPage.body}>
                <LegalPageLinks slug="terms" />
            </LegalPageLayout>
        </>
    );
}
