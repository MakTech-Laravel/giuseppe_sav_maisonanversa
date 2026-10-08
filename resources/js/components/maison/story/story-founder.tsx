import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { StoryFounderContent } from '@/types/story-page';

export function StoryFounder({ founder }: { founder: StoryFounderContent | null }) {
    if (founder === null) {
        return null;
    }

    return (
        <Section tone="cream2" className="py-20 md:py-28">
            <Wrap>
                <div className="grid items-stretch gap-10 bg-cream p-8 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] md:gap-14 md:p-12">
                    <StoryMediaFrame src={founder.imageUrl} ratio="3 / 4" className="w-full" />

                    <div className="flex flex-col">
                        <Eyebrow tone="gold2">{founder.eyebrow}</Eyebrow>
                        <h2 className="mt-4 font-serif text-[clamp(32px,4vw,44px)] font-medium text-choc">
                            {founder.name}
                        </h2>
                        <span className="mt-2 mb-8 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                            {founder.role}
                        </span>

                        <div className="space-y-5 text-[15px] leading-[1.85] text-choc3">
                            {founder.paragraphs.map((paragraph) => (
                                <p key={paragraph}>{paragraph}</p>
                            ))}
                        </div>

                        <p className="mt-10 self-end font-serif text-[28px] text-choc italic">
                            {founder.signature}
                        </p>
                    </div>
                </div>
            </Wrap>
        </Section>
    );
}
