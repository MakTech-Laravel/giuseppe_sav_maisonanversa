import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

type UnboxingStep = {
    num: string;
    title: string;
    desc: string;
};

export function ProductUnboxing({ steps = [] }: { steps?: UnboxingStep[] }) {
    const { t } = useTranslation();

    if (steps.length === 0) {
        return null;
    }

    return (
        <Section tone="dark">
            <Wrap>
                <Reveal className="mb-12 grid items-end gap-8 md:grid-cols-2">
                    <div>
                        <Eyebrow>{t('Wat in de Doos Zit')}</Eyebrow>
                        <h2 className="mt-3 font-serif text-[clamp(26px,3vw,40px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                            {t('De volledige Heritage Ervaring')}
                        </h2>
                    </div>
                    <p className="text-[15px] leading-[1.85] text-sand">
                        {t(
                            'Elke Heritage No.001 wordt geleverd als één complete ervaring. De volgorde is intentioneel.',
                        )}
                    </p>
                </Reveal>

                <div className="grid grid-cols-2 gap-0.5 ma-lg:grid-cols-6 md:grid-cols-3">
                    {steps.map((step) => (
                        <Reveal
                            key={step.num}
                            className="border border-gold/10 bg-white/3 px-5 pt-7 pb-6 transition-colors hover:border-gold/25 hover:bg-gold/5"
                        >
                            <div className="mb-3.5 font-serif text-[44px] leading-none font-light text-gold/18">
                                {step.num}
                            </div>
                            <div className="mb-2 font-sans text-[9px] font-medium tracking-[0.2em] text-gold uppercase">
                                {step.title === 'Founding Circle' ||
                                step.title === 'Heritage No.001'
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
