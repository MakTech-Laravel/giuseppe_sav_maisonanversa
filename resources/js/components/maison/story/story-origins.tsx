import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { StoryOriginsContent } from '@/types/story-page';

export function StoryOrigins({ origins }: { origins: StoryOriginsContent | null }) {
    if (origins === null) {
        return null;
    }

    return (
        <Section tone="cream" className="py-20 md:py-28">
            <Wrap className="mx-auto max-w-3xl text-center">
                <Eyebrow tone="gold2">{origins.eyebrow}</Eyebrow>
                <p className="mt-6 text-[15px] leading-[1.9] text-choc3">{origins.lead}</p>
                <h2 className="mt-10 font-serif text-[clamp(28px,4vw,44px)] leading-[1.2] font-medium text-choc">
                    {origins.title}
                </h2>
                <p className="mt-8 text-[15px] leading-[1.9] text-choc3">{origins.body}</p>
                <p className="mt-10 font-serif text-[clamp(22px,2.5vw,30px)] leading-[1.4] text-choc italic">
                    {origins.italic}
                </p>
                <p className="mt-8 text-[15px] leading-[1.9] text-choc3">{origins.close}</p>
            </Wrap>
        </Section>
    );
}
