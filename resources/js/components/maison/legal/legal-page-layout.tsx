import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LegalHtml } from '@/components/maison/legal/legal-html';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Section, Wrap } from '@/components/maison/ui/section';

type LegalPageLayoutProps = {
    title: string;
    body: string;
    children?: ReactNode;
    preview?: boolean;
};

export function LegalPageLayout({
    title,
    body,
    children,
    preview = false,
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
                    <LegalHtml html={body} />

                    {children ? (
                        <div className="mt-8 text-[15px] leading-[1.8] text-choc3">
                            {children}
                        </div>
                    ) : null}

                    {preview ? null : (
                        <MaisonButton
                            as={MaisonLink}
                            to="home"
                            variant="choc"
                            className="mt-7.5"
                        >
                            {t('← Terug naar home')}
                        </MaisonButton>
                    )}
                </Wrap>
            </Section>
        </>
    );
}
