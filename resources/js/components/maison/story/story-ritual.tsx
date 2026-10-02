import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

const STEPS = [
    {
        num: '01',
        title: 'Aankleden',
        desc: 'Met zorg, ook op een gewone ochtend.',
    },
    {
        num: '02',
        title: 'Koffie',
        desc: 'Op een terras, terwijl de stad wakker wordt.',
    },
    {
        num: '03',
        title: 'De stad',
        desc: 'Even rondlopen. Kijken, luisteren, proeven.',
    },
    {
        num: '04',
        title: 'De baan',
        desc: 'Sportkleren aan. En spelen.',
    },
] as const;

export function StoryRitual() {
    const { t } = useTranslation();

    return (
        <Section tone="cream2" className="py-20 md:py-28">
            <Wrap className="text-center">
                <Eyebrow tone="gold2">{t('02 · Het ritueel')}</Eyebrow>
                <h2 className="mx-auto mt-4 max-w-3xl font-serif text-[clamp(28px,3.5vw,42px)] leading-[1.2] font-medium text-choc">
                    {t('Stijl stopt niet waar sport begint.')}
                </h2>

                <div className="mt-14 grid grid-cols-1 divide-y divide-choc/15 border-t border-choc/15 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4">
                    {STEPS.map((step, index) => (
                        <div
                            key={step.num}
                            className={`px-6 py-8 text-left ${
                                index > 0 ? 'sm:border-l sm:border-choc/15' : ''
                            } ${index >= 2 ? 'sm:border-t sm:border-choc/15 lg:border-t-0' : ''}`}
                        >
                            <span className="font-sans text-[11px] tracking-[0.2em] text-gold2">
                                {step.num}
                            </span>
                            <h3 className="mt-3 mb-2 font-serif text-[22px] font-medium text-choc">
                                {t(step.title)}
                            </h3>
                            <p className="text-[14px] leading-[1.75] text-choc3">
                                {t(step.desc)}
                            </p>
                        </div>
                    ))}
                </div>

                <p className="mx-auto mt-12 max-w-2xl text-[15px] leading-[1.9] text-choc3">
                    {t(
                        'Voor ons horen die momenten bij elkaar. De koffie ervoor, het gesprek erna, en alles wat je draagt, van het terras tot op de baan.',
                    )}
                </p>
            </Wrap>
        </Section>
    );
}
