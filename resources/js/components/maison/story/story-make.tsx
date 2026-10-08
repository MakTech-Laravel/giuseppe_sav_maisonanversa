import { MaisonLink } from '@/components/maison/maison-link';
import { StoryMediaFrame } from '@/components/maison/story/story-media-frame';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { StoryMakeContent } from '@/types/story-page';

const CARD_ASSETS = [
    'heritage-001-detail-gravure',
    'heritage-001-front',
    'heritage-001-lifestyle-court',
] as const;

export function StoryMake({ make }: { make: StoryMakeContent | null }) {
    if (make === null) {
        return null;
    }

    return (
        <Section tone="dark" className="py-20 md:py-28">
            <Wrap className="grid items-start gap-14 md:grid-cols-2 md:gap-16">
                <div>
                    <Eyebrow>{make.eyebrow}</Eyebrow>
                    <h2 className="mt-4 mb-6 font-serif text-[clamp(32px,4vw,48px)] font-medium text-cream">
                        {make.title}
                    </h2>
                    <p className="mb-5 text-[16px] leading-[1.9] text-sand">{make.bodyOne}</p>
                    <p className="mb-10 text-[16px] leading-[1.9] text-sand">{make.bodyTwo}</p>
                    <MaisonButton as={MaisonLink} href={make.buttonHref} variant="hero">
                        {make.buttonLabel}
                    </MaisonButton>
                </div>

                <div className="grid grid-cols-3 items-end gap-3 md:gap-4">
                    {CARD_ASSETS.map((asset, index) => (
                        <div
                            key={asset}
                            className={index === 1 ? 'translate-y-6 md:translate-y-10' : ''}
                        >
                            <StoryMediaFrame
                                src={make.imageUrls[index]}
                                asset={make.imageUrls[index] ? undefined : asset}
                                ratio={index === 0 ? '3 / 4' : '3 / 5'}
                                className="w-full"
                            />
                            <p className="mt-3 font-sans text-[9px] tracking-[0.2em] text-sand/70 uppercase">
                                {make.captions[index]}
                            </p>
                        </div>
                    ))}
                </div>
            </Wrap>
        </Section>
    );
}
