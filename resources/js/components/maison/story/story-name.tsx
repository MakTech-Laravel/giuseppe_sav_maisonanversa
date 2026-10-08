import { useTranslation } from 'react-i18next';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryName() {
    const { t } = useTranslation();

    return (
        <Section tone="cream" className="py-20 md:py-28">
            <Wrap className="grid items-center gap-12 border-y border-choc/10 py-14 md:grid-cols-2 md:gap-16 md:py-20">
                <StoryMediaFrame
                    asset="maison-facade-house"
                    contain
                    className="mx-auto w-[78%] max-w-72 bg-cream md:w-full md:max-w-none"
                />

                <div>
                    <Eyebrow
                        tone="gold"
                        className="md:text-[11px] md:tracking-[0.32em]"
                    >
                        {t('04 · De naam')}
                    </Eyebrow>
                    <div className="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1 md:mt-6 md:gap-x-4">
                        <h2 className="font-serif text-[40px] leading-none font-medium text-choc md:text-[clamp(56px,6.5vw,80px)]">
                            Anversa
                        </h2>
                        <span className="font-serif text-[16px] leading-none text-stone italic md:text-[22px]">
                            /anˈvɛrsa/
                        </span>
                    </div>
                    <p className="mt-5 max-w-[48ch] text-[15px] leading-[1.8] text-stone md:mt-8 md:text-[18px] md:leading-[1.85]">
                        {t(
                            'Anversa is de Italiaanse naam voor Antwerpen. We kozen hem voor een stad die altijd over haar grenzen heen keek, en voor een huis met wortels in Antwerpen en een blik op Europa.',
                        )}
                    </p>
                </div>
            </Wrap>
        </Section>
    );
}
