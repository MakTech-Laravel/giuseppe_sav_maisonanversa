import { Wrap } from '@/components/maison/ui/section';
import type { StoryQuoteContent } from '@/types/story-page';

export function StoryQuote({ quote }: { quote: StoryQuoteContent | null }) {
    if (quote === null) {
        return null;
    }

    return (
        <section className="bg-cream py-16 text-center md:py-20">
            <Wrap>
                <p className="font-serif text-[clamp(26px,3.5vw,40px)] leading-[1.3] text-choc italic">
                    {quote.line}
                </p>
            </Wrap>
        </section>
    );
}
