import { useTranslation } from 'react-i18next';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryName() {
    const { t } = useTranslation();

    return (
        <Section tone="cream" className="py-20 md:py-28">
            <Wrap className="grid items-center gap-10 border-y border-choc/10 px-0 py-14 pr-8 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.35fr)] md:gap-20 md:px-0 md:py-20 md:pr-32 md:pl-0">
                <StoryMediaFrame
                    asset="maison-facade-house"
                    contain
                    className="w-full justify-self-start bg-cream"
                />

                <div className="w-full px-8 md:px-0 md:pr-2">
                    <Eyebrow
                        tone="gold"
                        className="text-[11px] tracking-[0.32em]"
                    >
                        {t('04 · De naam')}
                    </Eyebrow>
                    <div className="mt-6 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                        <h2 className="font-serif text-[clamp(56px,6.5vw,80px)] leading-none font-medium text-choc">
                            Anversa
                        </h2>
                        <span className="font-serif text-[22px] leading-none text-stone italic">
                            /anˈvɛrsa/
                        </span>
                    </div>
                    <p className="mt-8 max-w-[48ch] text-[18px] leading-[1.85] text-stone">
                        {t(
                            'Anversa is de Italiaanse naam voor Antwerpen. We kozen hem voor een stad die altijd over haar grenzen heen keek, en voor een huis met wortels in Antwerpen en een blik op Europa.',
                        )}
                    </p>
                </div>
            </Wrap>
        </Section>
    );
}
