import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { StoryCityContent } from '@/types/story-page';

export function StoryCity({ city }: { city: StoryCityContent | null }) {
    if (city === null) {
        return null;
    }

    return (
        <Section tone="cream" className="py-20 md:py-28">
            <Wrap className="grid items-center gap-12 md:grid-cols-2 md:gap-16">
                <div>
                    <Eyebrow tone="gold2">{city.eyebrow}</Eyebrow>
                    <h2 className="mt-4 mb-6 font-serif text-[clamp(32px,4vw,48px)] font-medium text-choc">
                        {city.title}
                    </h2>
                    <p className="mb-5 text-[16px] leading-[1.9] text-choc3">{city.bodyOne}</p>
                    <p className="mb-8 text-[16px] leading-[1.9] text-choc3">{city.bodyTwo}</p>
                    <p className="font-serif text-[20px] leading-[1.5] text-choc italic">{city.closer}</p>
                </div>

                <StoryMediaFrame
                    src={city.imageUrl}
                    asset={city.imageUrl ? undefined : 'antwerp-cityscape'}
                    ratio="4 / 5"
                />
            </Wrap>
        </Section>
    );
}
