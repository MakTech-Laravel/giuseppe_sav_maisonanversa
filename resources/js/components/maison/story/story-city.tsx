import { useTranslation } from 'react-i18next';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryCity() {
    const { t } = useTranslation();

    return (
        <Section tone="cream" className="py-20 md:py-28">
            <Wrap className="grid items-center gap-12 md:grid-cols-2 md:gap-16">
                <div>
                    <Eyebrow tone="gold2">{t('01 · De stad')}</Eyebrow>
                    <h2 className="mt-4 mb-6 font-serif text-[clamp(32px,4vw,48px)] font-medium text-choc">
                        {t('Antwerpen')}
                    </h2>
                    <p className="mb-5 text-[16px] leading-[1.9] text-choc3">
                        {t(
                            'Elke dag begint hier op dezelfde manier. Een koffie terwijl de stad langzaam wakker wordt. De eerste tram rijdt voorbij, winkels gaan open, terrassen worden klaargezet.',
                        )}
                    </p>
                    <p className="mb-8 text-[16px] leading-[1.9] text-choc3">
                        {t(
                            'Antwerpen heeft altijd naar buiten gekeken. Een havenstad waar handel, cultuur, mode en ideeën samenkomen. Historisch zonder stil te staan. Europees, maar met een eigen karakter.',
                        )}
                    </p>
                    <p className="font-serif text-[20px] leading-[1.5] text-choc italic">
                        {t('Daar begint Maison Anversa.')}
                    </p>
                </div>

                <StoryMediaFrame asset="antwerp-cityscape" ratio="4 / 5" />
            </Wrap>
        </Section>
    );
}
