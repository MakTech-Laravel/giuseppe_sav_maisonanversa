import { useTranslation } from 'react-i18next';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryFounder() {
    const { t } = useTranslation();

    return (
        <Section tone="cream2" className="py-20 md:py-28">
            <Wrap>
                <div className="grid items-stretch gap-10 bg-cream p-8 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] md:gap-14 md:p-12">
                    <StoryMediaFrame ratio="3 / 4" className="w-full" />

                    <div className="flex flex-col">
                        <Eyebrow tone="gold2">{t('06 · De oprichter')}</Eyebrow>
                        <h2 className="mt-4 font-serif text-[clamp(32px,4vw,44px)] font-medium text-choc">
                            {t('Yusuf Savran')}
                        </h2>
                        <span className="mt-2 mb-8 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                            {t('Oprichter, Maison Anversa')}
                        </span>

                        <div className="space-y-5 text-[15px] leading-[1.85] text-choc3">
                            <p>
                                {t(
                                    'Ik heb altijd gehouden van dingen die met aandacht gemaakt worden. Van kleding die jaren meegaat. Van oude gebouwen en de verhalen erachter. Van koffie, steden en natuurlijk sport.',
                                )}
                            </p>
                            <p>
                                {t(
                                    'Toen ik padel begon te spelen, merkte ik iets op. De wereld die ik mooi vond buiten de baan, vond ik nauwelijks terug op de baan.',
                                )}
                            </p>
                            <p>{t('Daar ontstond Maison Anversa.')}</p>
                            <p>
                                {t(
                                    'Niet met het idee om zomaar een racket te maken, maar om stap voor stap een huis rond sport te bouwen — met aandacht voor design, materiaal, cultuur en de momenten rondom het spel.',
                                )}
                            </p>
                            <p>
                                {t(
                                    'We beginnen met padel. Met honderd rackets.',
                                )}
                            </p>
                            <p>
                                {t(
                                    'Waar het eindigt, weet ik nog niet. En misschien is dat juist het mooie eraan.',
                                )}
                            </p>
                        </div>

                        <p className="mt-10 self-end font-serif text-[28px] text-choc italic">
                            Yusuf
                        </p>
                    </div>
                </div>
            </Wrap>
        </Section>
    );
}
