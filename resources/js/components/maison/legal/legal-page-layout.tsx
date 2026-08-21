import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Section, Wrap } from '@/components/maison/ui/section';

type LegalPageLayoutProps = {
    title: string;
    body: string;
};

export function LegalPageLayout({
    title,
    body,
}: LegalPageLayoutProps) {
    const { t } = useTranslation();

    return (
        <>
            <PageHero
                eyebrow={t('Juridisch')}
                title={<em>{t(title)}</em>}
                subtitle={
                    <p className="font-sans text-[12px] tracking-[0.1em] text-sand uppercase">
                        {t('Laatst bijgewerkt: juli 2026')}
                    </p>
                }
            />

            <Section tone="cream">
                <Wrap className="mx-auto max-w-[760px]">
                    <div className="font-sans text-[14px] leading-[1.85] whitespace-pre-line text-choc3">
                        {body}
                    </div>

                    <MaisonButton
                        as={MaisonLink}
                        to="home"
                        variant="choc"
                        className="mt-7.5"
                    >
                        {t('← Terug naar home')}
                    </MaisonButton>
                </Wrap>
            </Section>
        </>
    );
}
