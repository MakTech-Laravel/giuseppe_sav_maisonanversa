import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Section, Wrap } from '@/components/maison/ui/section';

type LegalPageLayoutProps = {
    /** Dutch source key for the document title and page `<Head>`. */
    titleKey: string;
    introKey: string;
    noteKey: string;
    children: ReactNode;
};

export function LegalPageLayout({
    titleKey,
    introKey,
    noteKey,
    children,
}: LegalPageLayoutProps) {
    const { t } = useTranslation();

    return (
        <>
            <PageHero
                eyebrow={t('Juridisch')}
                title={<em>{t(titleKey)}</em>}
                subtitle={
                    <p className="font-sans text-[12px] tracking-[0.1em] text-sand uppercase">
                        {t('Laatst bijgewerkt: juli 2026')}
                    </p>
                }
            />

            <Section tone="cream">
                <Wrap className="mx-auto max-w-[760px]">
                    <p className="mb-7 font-serif text-[18px] leading-[1.7] text-choc3 italic">
                        {t(introKey)}
                    </p>

                    <div className="font-sans text-[14px] leading-[1.85] text-choc3 [&_h2]:mt-8.5 [&_h2]:mb-2.5 [&_h2]:font-serif [&_h2]:text-[21px] [&_h2]:font-medium [&_h2]:tracking-[0.01em] [&_h2]:text-choc [&_li]:mb-1.5 [&_p+a]:text-gold2 [&_p+a]:underline [&_p+a]:underline-offset-2 [&_p+a]:hover:text-gold [&_p]:mb-0 [&_ul]:list-disc [&_ul]:pl-5">
                        {children}
                    </div>

                    <div className="mt-8.5 rounded-md border border-gold/40 bg-gold/6 px-5.5 py-4.5 font-sans text-[12px] tracking-[0.12em] text-choc uppercase">
                        {t(noteKey)}
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

export function LegalHeading({ children }: { children: ReactNode }) {
    return <h2>{children}</h2>;
}

export function LegalParagraph({ children }: { children: ReactNode }) {
    return <p className="mb-0">{children}</p>;
}

export function LegalList({ children }: { children: ReactNode }) {
    return <ul>{children}</ul>;
}

export function LegalListItem({ children }: { children: ReactNode }) {
    return <li>{children}</li>;
}
