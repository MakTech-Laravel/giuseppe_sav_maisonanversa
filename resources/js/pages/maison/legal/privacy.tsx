import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { LegalPageLinks } from '@/components/maison/legal/legal-page-links';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Privacy({
    legalPage,
}: {
    legalPage: { slug: string; body: string };
}) {
    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout title="Privacybeleid" body={legalPage.body}>
                <LegalPageLinks slug="privacy" />
            </LegalPageLayout>
        </>
    );
}
