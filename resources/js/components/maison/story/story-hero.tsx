import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Wrap } from '@/components/maison/ui/section';
import type { StoryHeroContent } from '@/types/story-page';

export function StoryHero({ hero }: { hero: StoryHeroContent | null }) {
    if (hero === null) {
        return null;
    }

    return (
        <section className="relative overflow-hidden border-b border-gold/10 bg-choc2 text-cream">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(141,112,90,0.025)_1px,transparent_1px),linear-gradient(90deg,rgba(141,112,90,0.025)_1px,transparent_1px)] bg-[size:48px_48px]"
            />

            <Wrap className="relative grid items-center gap-12 py-18 md:grid-cols-2 md:gap-16 md:py-24">
                <div className="text-center md:text-left">
                    <Eyebrow className="mb-0 text-center md:text-left">{hero.eyebrow}</Eyebrow>
                    <GoldRule className="mx-auto md:mx-0" />
                    <h1 className="font-serif text-[clamp(36px,5vw,64px)] leading-[1.1] font-normal [&_em]:text-gold [&_em]:italic">
                        {hero.title} <em>{hero.titleAccent}</em>
                    </h1>
                    <p className="mt-4 font-sans text-[17px] leading-[1.8] text-sand">{hero.body}</p>
                </div>

                <StoryMediaFrame
                    src={hero.imageUrl}
                    ratio="4 / 5"
                    loading="eager"
                    className="mx-auto w-full max-w-md md:max-w-none"
                />
            </Wrap>
        </section>
    );
}
