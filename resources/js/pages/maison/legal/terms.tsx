import { useTranslation } from 'react-i18next';
import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Terms({
    legalPage,
}: {
    legalPage: { slug: string; body: string };
}) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <LegalPageLayout
                title={t('Algemene voorwaarden')}
                body={legalPage.body}
            />
            <div className="sr-only">
                <MaisonLink to="care">{t('zorg- en garantiebeleid')}</MaisonLink>
                <MaisonLink to="shipping">{t('verzend- en retourbeleid')}</MaisonLink>
            </div>
        </>
    );
}
