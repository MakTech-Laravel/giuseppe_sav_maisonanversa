import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { LegalPageLinks } from '@/components/maison/legal/legal-page-links';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Withdrawal({
    legalPage,
}: {
    legalPage: { slug: string; body: string };
}) {
    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout
                title="Model withdrawal form"
                body={legalPage.body}
            >
                <LegalPageLinks slug="withdrawal" />
            </LegalPageLayout>
        </>
    );
}
