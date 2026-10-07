import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl } from '@/lib/maison-navigation';

const CARDS = [
    { label: 'Het certificaat', asset: 'heritage-001-detail-gravure' as const },
    { label: 'Het paspoort', asset: 'heritage-001-front' as const },
    { label: 'De brief', asset: 'heritage-001-lifestyle-court' as const },
] as const;

export function StoryMake() {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Section tone="dark" className="py-20 md:py-28">
                <Wrap className="grid items-start gap-14 md:grid-cols-2 md:gap-16">
                    <div>
                        <Eyebrow>{t('05 · Wat we maken')}</Eyebrow>
                        <h2 className="mt-4 mb-6 font-serif text-[clamp(32px,4vw,48px)] font-medium text-cream">
                            Heritage No.001
                        </h2>
                        <p className="mb-5 text-[16px] leading-[1.9] text-sand">
                            {t(
                                'Ons eerste hoofdstuk: een padelracket in een oplage van honderd stuks. Elk exemplaar krijgt een eigen nummer, een certificaat, een paspoort en een brief. Niet omdat het moet, maar omdat een eerste product een eerste hoofdstuk verdient.',
                            )}
                        </p>
                        <p className="mb-10 text-[16px] leading-[1.9] text-sand">
                            {t(
                                'De eerste honderd exemplaren vormen samen de Founding Circle — genummerd van 001 tot 100 en vastgelegd in het register van het huis.',
                            )}
                        </p>
                        <MaisonButton
                            as={MaisonLink}
                            href={foundingProductUrl(locale)}
                            variant="hero"
                        >
                            {t('Ontdek Heritage No.001')}
                        </MaisonButton>
                    </div>

                    <div className="grid grid-cols-3 items-end gap-3 md:gap-4">
                        {CARDS.map((card, index) => (
                            <div
                                key={card.label}
                                className={
                                    index === 1
                                        ? 'translate-y-6 md:translate-y-10'
                                        : ''
                                }
                            >
                                <StoryMediaFrame
                                    asset={card.asset}
                                    ratio={index === 0 ? '3 / 4' : '3 / 5'}
                                    className="w-full"
                                />
                                <p className="mt-3 font-sans text-[9px] tracking-[0.2em] text-sand/70 uppercase">
                                    {t(card.label)}
                                </p>
                            </div>
                        ))}
                    </div>
                </Wrap>
            </Section>

            <section className="bg-cream py-16 text-center md:py-20">
                <Wrap>
                    <p className="font-serif text-[clamp(26px,3.5vw,40px)] leading-[1.3] text-choc italic">
                        {t('Padel is ons begin. Niet ons einde.')}
                    </p>
                </Wrap>
            </section>
        </>
    );
}
