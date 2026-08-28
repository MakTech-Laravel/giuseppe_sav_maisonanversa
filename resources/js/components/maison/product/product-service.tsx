import { SectionIcon } from '@/components/maison/product/section-icon';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { ProductSection } from '@/types/product';

export function ProductService({ section }: { section?: ProductSection }) {
    const items = section?.items ?? [];

    if (items.length === 0) {
        return null;
    }

    return (
        <Section tone="cream2">
            <Wrap>
                <Reveal className="mb-13 text-center">
                    {section?.eyebrow ? (
                        <Eyebrow tone="gold2">{section.eyebrow}</Eyebrow>
                    ) : null}
                    <GoldRule center className="mx-auto" />
                    {section?.heading ? (
                        <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium tracking-[0.04em] uppercase">
                            {section.heading}
                        </h2>
                    ) : null}
                    {section?.intro ? (
                        <p className="mt-3.5 font-sans text-xs text-stone">
                            {section.intro}
                        </p>
                    ) : null}
                </Reveal>

                <div className="grid gap-px bg-gold/20 md:grid-cols-3">
                    {items.map((item) => (
                        <Reveal key={item.id} className="bg-cream px-8.5 py-11">
                            {item.icon ? (
                                <div className="mb-4 text-2xl text-gold2">
                                    <SectionIcon icon={item.icon} />
                                </div>
                            ) : null}
                            <h3 className="mb-3 font-serif text-[21px] text-choc">
                                {item.title}
                            </h3>
                            <p className="text-[13px] leading-[1.75] text-choc3">
                                {item.body}
                            </p>
                        </Reveal>
                    ))}
                </div>
            </Wrap>
        </Section>
    );
}
