import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { TextLink } from '@/components/maison/ui/text-link';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl } from '@/lib/maison-navigation';

const STEPS = [
    {
        num: '01',
        title: 'Welkomstkaart',
        desc: 'The first thing you see. Personal, warm, hand-drawn.',
    },
    {
        num: '02',
        title: 'Oprichtersbrief',
        desc: 'The story of Maison Anversa. Written by Yusuf.',
    },
    {
        num: '03',
        title: 'Heritage Paspoort',
        desc: "24 pages. Leather cover. Your place in the story.",
    },
    {
        num: '04',
        title: 'Heritage Certificaat',
        desc: "Founder's seal. Signature. Your edition number.",
    },
    {
        num: '05',
        title: 'Founding Circle',
        desc: 'Invitation to the first 100 of Maison Anversa.',
    },
    {
        num: '06',
        title: 'Heritage Nº 001',
        desc: 'In a premium canvas dust cover. Last. As it should be.',
    },
] as const;

export function HomeUnboxing() {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <Section tone="dark">
            <Wrap>
                <Reveal className="mb-12 grid items-end gap-8 md:grid-cols-[1fr_2fr] md:gap-12">
                    <div>
                        <Eyebrow>{t('The Heritage Experience')}</Eyebrow>
                        <h2 className="mt-3 font-serif text-[clamp(26px,3vw,40px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                            {t('More than')}
                            <br />
                            {t('a Racket.')}
                        </h2>
                    </div>
                    <div>
                        <p className="mb-5 text-[15px] leading-[1.85] text-sand">
                            {t(
                                'Each Heritage Nº 001 package is a complete experience. Every element is intentionally chosen. The order is never accidental.',
                            )}
                        </p>
                        <TextLink
                            as={MaisonLink}
                            tone="light"
                            href={foundingProductUrl(locale)}
                        >
                            {t('Discover the full experience →')}
                        </TextLink>
                    </div>
                </Reveal>

                <div className="grid grid-cols-2 gap-0.5 ma-lg:grid-cols-6 md:grid-cols-3">
                    {STEPS.map((step) => (
                        <Reveal
                            key={step.num}
                            className="border border-gold/10 bg-white/3 px-5 pt-7 pb-6 transition-colors hover:border-gold/25 hover:bg-gold/5"
                        >
                            <div className="mb-3.5 font-serif text-[44px] leading-none font-light text-gold/18">
                                {step.num}
                            </div>
                            <div className="mb-2 font-sans text-[9px] font-medium tracking-[0.2em] text-gold uppercase">
                                {step.title === 'Founding Circle' ||
                                step.title === 'Heritage Nº 001'
                                    ? step.title
                                    : t(step.title)}
                            </div>
                            <div className="text-[12px] leading-[1.65] text-sand">
                                {t(step.desc)}
                            </div>
                        </Reveal>
                    ))}
                </div>
            </Wrap>
        </Section>
    );
}
