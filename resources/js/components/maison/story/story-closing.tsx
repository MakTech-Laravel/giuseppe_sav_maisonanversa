import { Section, Wrap } from '@/components/maison/ui/section';
import type { StoryClosingContent } from '@/types/story-page';

export function StoryClosing({ closing }: { closing: StoryClosingContent | null }) {
    if (closing === null) {
        return null;
    }

    return (
        <Section tone="cream" className="border-t border-gold/15 py-16 md:py-20">
            <Wrap className="text-center">
                <p className="font-serif text-[clamp(18px,2vw,24px)] leading-[1.5] text-choc italic">
                    {closing.line}
                </p>
                <p className="mt-4 font-sans text-[10px] tracking-[0.3em] text-gold2 uppercase">
                    {closing.place}
                </p>
            </Wrap>
        </Section>
    );
}
