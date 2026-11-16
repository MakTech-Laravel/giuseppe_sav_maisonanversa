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
                    <Eyebrow>{t('Rooted in Antwerp')}</Eyebrow>
                    <GoldRule />
                    <h2 className="mb-6 font-serif text-[clamp(32px,4vw,52px)] leading-[1.1] font-normal tracking-[0.04em] uppercase">
                        {t('A city that always looked outward.')}
                    </h2>
                    <p className="mb-7 text-[15px] leading-[1.85] text-sand">
                        {t(
                            'Every day here begins the same way: a coffee while the city wakes up. Antwerp is a port city where trade, culture, fashion and ideas meet. Historic without standing still. Anversa is the Italian name for Antwerp: a house rooted in this city, looking out over Europe.',
                        )}
                    </p>
                    <div className="flex flex-wrap gap-12">
                        <Figure
                            value="2026"
                            line1={t('Founding')}
                            line2={t('in Antwerp')}
                        />
                        <Figure
                            value="100"
                            line1={t('Pieces')}
                            line2="Founding Edition"
                        />
                        <Figure value="1" line1={t('City that carries us')} />
                    </div>
                </Reveal>

                <Reveal className="relative aspect-4/5 w-full overflow-hidden">
                    <PlaceholderImage
                        asset="antwerp-cityscape"
                        ratio={null}
                        alt={t('Antwerp at golden hour')}
                        captioned={false}
                        overlay="linear-gradient(to top, rgba(41,28,24,0.5) 0%, transparent 50%)"
                        className="absolute inset-0 h-full w-full [&_img]:object-cover [&_img]:object-center"
                    />
                    <div className="absolute bottom-6 left-6 font-sans text-[9px] font-light tracking-[0.3em] text-gold uppercase">
                        {t('Antwerp · Belgium · Est. 2026')}
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
    line2?: string;
}) {
    return (
        <div>
            <div className="font-serif text-[36px] leading-none font-light text-gold lining-nums">
                {value}
            </div>
            <div className="mt-1.5 font-sans text-[9px] font-light tracking-[0.2em] text-sand uppercase">
                {line1}
                {line2 ? (
                    <>
                        <br />
                        {line2}
                    </>
                ) : null}
            </div>
        </div>
    );
}
