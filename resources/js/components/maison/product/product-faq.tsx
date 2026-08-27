import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonAccordion } from '@/components/maison/ui/maison-accordion';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { ProductFaqItem, ProductSection } from '@/types/product';

export function ProductFaq({
    faqs,
    section,
}: {
    faqs: ProductFaqItem[];
    section?: ProductSection;
}) {
    if (faqs.length === 0) {
        return null;
    }

    return (
        <Section tone="cream">
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
                </Reveal>

                <MaisonAccordion
                    className="mx-auto max-w-205"
                    entries={faqs.map((item) => ({
                        value: `faq-${item.id}`,
                        question: item.question,
                        answer: item.answer,
                    }))}
                />
            </Wrap>
        </Section>
    );
}
