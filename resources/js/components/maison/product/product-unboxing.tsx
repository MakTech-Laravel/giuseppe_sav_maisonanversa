import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { ProductSection } from '@/types/product';

export function ProductUnboxing({ section }: { section?: ProductSection }) {
    const items = section?.items ?? [];

    if (items.length === 0) {
        return null;
    }

    return (
        <Section tone="dark">
            <Wrap>
                <Reveal className="mb-12 grid items-end gap-8 md:grid-cols-2">
                    <div>
                        {section?.eyebrow ? (
                            <Eyebrow>{section.eyebrow}</Eyebrow>
                        ) : null}
                        {section?.heading ? (
                            <h2 className="mt-3 font-serif text-[clamp(26px,3vw,40px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                                {section.heading}
                            </h2>
                        ) : null}
                    </div>
                    {section?.intro ? (
                        <p className="text-[15px] leading-[1.85] text-sand">
                            {section.intro}
                        </p>
                    ) : null}
                </Reveal>

                <div className="grid grid-cols-2 gap-0.5 ma-lg:grid-cols-6 md:grid-cols-3">
                    {items.map((item) => (
                        <Reveal
                            key={item.id}
                            className="border border-gold/10 bg-white/3 px-5 pt-7 pb-6 transition-colors hover:border-gold/25 hover:bg-gold/5"
                        >
                            <div className="mb-3.5 font-serif text-[44px] leading-none font-light text-gold/18">
                                {item.number_label}
                            </div>
                            <div className="mb-2 font-sans text-[9px] font-medium tracking-[0.2em] text-gold uppercase">
                                {item.title}
                            </div>
                            <div className="text-[12px] leading-[1.65] text-sand">
                                {item.body}
                            </div>
                        </Reveal>
                    ))}
                </div>
            </Wrap>
        </Section>
    );
}
