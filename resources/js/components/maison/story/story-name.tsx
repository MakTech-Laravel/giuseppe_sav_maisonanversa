import { useTranslation } from 'react-i18next';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryName() {
    const { t } = useTranslation();

    return (
        <Section tone="cream2" className="py-20 md:py-28">
            <Wrap className="grid items-center gap-12 border-y border-choc/10 py-12 md:grid-cols-2 md:gap-16 md:py-16">
                <StoryMediaFrame
                    asset="maison-facade-house"
                    ratio="3 / 2"
                    contain
                    className="bg-cream"
                />

                <div>
                    <Eyebrow tone="gold2">{t('04 · De naam')}</Eyebrow>
                    <div className="mt-4 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                        <h2 className="font-serif text-[clamp(36px,4vw,52px)] font-medium text-choc">
                            Anversa
                        </h2>
                        <span className="font-serif text-[18px] text-choc3 italic">
                            /anˈvɛrsa/
                        </span>
                    </div>
                    <p className="mt-6 text-[16px] leading-[1.9] text-choc3">
                        {t(
                            'Anversa is de Italiaanse naam voor Antwerpen. We kozen hem voor een stad die altijd over haar grenzen heen keek, en voor een huis met wortels in Antwerpen en een blik op Europa.',
                        )}
                    </p>
                </div>
            </Wrap>
        </Section>
    );
}
