import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { StoryRitualContent } from '@/types/story-page';

export function StoryRitual({ ritual }: { ritual: StoryRitualContent | null }) {
    if (ritual === null) {
        return null;
    }

    return (
        <Section tone="cream2" className="py-20 md:py-28">
            <Wrap className="text-center">
                <Eyebrow tone="gold2">{ritual.eyebrow}</Eyebrow>
                <h2 className="mx-auto mt-4 max-w-3xl font-serif text-[clamp(28px,3.5vw,42px)] leading-[1.2] font-medium text-choc">
                    {ritual.title}
                </h2>

                <div className="mt-14 grid grid-cols-1 divide-y divide-choc/15 border-t border-choc/15 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4">
                    {ritual.steps.map((step, index) => (
                        <div
                            key={step.number}
                            className={`px-6 py-8 text-left ${
                                index > 0 ? 'sm:border-l sm:border-choc/15' : ''
                            } ${index >= 2 ? 'sm:border-t sm:border-choc/15 lg:border-t-0' : ''}`}
                        >
                            <span className="font-sans text-[11px] tracking-[0.2em] text-gold2">
                                {step.number}
                            </span>
                            <h3 className="mt-3 mb-2 font-serif text-[22px] font-medium text-choc">
                                {step.title}
                            </h3>
                            <p className="text-[14px] leading-[1.75] text-choc3">{step.body}</p>
                        </div>
                    ))}
                </div>

                <p className="mx-auto mt-12 max-w-2xl text-[15px] leading-[1.9] text-choc3">
                    {ritual.footer}
                </p>
            </Wrap>
        </Section>
    );
}
