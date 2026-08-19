import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

export function HomeAntwerp() {
    const { t } = useTranslation();

    return (
        <Section tone="dark">
            <Wrap className="grid items-center gap-10 ma-lg:grid-cols-[1.1fr_1fr] ma-lg:gap-16">
                <Reveal>
                    <Eyebrow>{t('Geworteld in Antwerpen')}</Eyebrow>
                    <GoldRule />
                    <h2 className="mb-6 font-serif text-[clamp(32px,4vw,52px)] leading-[1.1] font-normal tracking-[0.04em] uppercase">
                        {t('Een stad van')}
                        <br />
                        {t('meesters.')}
                        <br />
                        {t('Een huis van')}
                        <br />
                        {t('erfgoed.')}
                    </h2>
                    <p className="mb-4 text-[15px] leading-[1.85] text-sand">
                        {t(
                            'Antwerpen is geen toevallige keuze. Eeuwenlang een stad van ambachtslieden, handelaren en kunstenaars — van diamantslijpers tot meester-timmerlieden. Een plaats waar geduld, precisie en schoonheid hand in hand gaan.',
                        )}
                    </p>
                    <p className="mb-7 text-[15px] leading-[1.85] text-sand">
                        {t(
                            'Maison Anversa draagt die geest in zich. Elk racket wordt ontworpen met dezelfde eerbied voor materiaal en detail die deze stad eeuwenlang definieerde. Antwerpen is niet onze achtergrond — het is onze overtuiging.',
                        )}
                    </p>
                    <div className="flex flex-wrap gap-12">
                        <Figure
                            value="2026"
                            line1={t('Oprichting')}
                            line2={t('in Antwerpen')}
                        />
                        <Figure
                            value="100"
                            line1={t('Stuks')}
                            line2="Founding Edition"
                        />
                        <Figure
                            value="1"
                            line1={t('Stad die')}
                            line2={t('ons draagt')}
                        />
                    </div>
                </Reveal>

                <Reveal className="relative aspect-4/5 w-full overflow-hidden">
                    <PlaceholderImage
                        asset="antwerp-cityscape"
                        ratio={null}
                        alt="Antwerpen bij gouden uur"
                        captioned={false}
                        overlay="linear-gradient(to top, rgba(41,28,24,0.5) 0%, transparent 50%)"
                        className="absolute inset-0 h-full w-full [&_img]:object-cover [&_img]:object-center"
                    />
                    <div className="absolute bottom-6 left-6 font-sans text-[9px] font-light tracking-[0.3em] text-gold uppercase">
                        Antwerp · Belgium · Est. 2026
                    </div>
                </Reveal>
            </Wrap>
        </Section>
    );
}

function Figure({
    value,
    line1,
    line2,
}: {
    value: string;
    line1: string;
    line2: string;
}) {
    return (
        <div>
            <div className="font-serif text-[36px] leading-none font-light text-gold lining-nums">
                {value}
            </div>
            <div className="mt-1.5 font-sans text-[9px] font-light tracking-[0.2em] text-sand uppercase">
                {line1}
                <br />
                {line2}
            </div>
        </div>
    );
}
