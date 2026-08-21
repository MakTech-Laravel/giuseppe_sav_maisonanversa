import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonAccordion } from '@/components/maison/ui/maison-accordion';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

type ProductFaqItem = {
    question: string;
    answer: string;
};

export function ProductFaq({ faqs }: { faqs: ProductFaqItem[] }) {
    const { t } = useTranslation();

    return (
        <Section tone="cream">
            <Wrap>
                <Reveal className="mb-13 text-center">
                    <Eyebrow tone="gold2">{t('Vragen')}</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium tracking-[0.04em] uppercase">
                        {t('Veelgestelde vragen.')}
                    </h2>
                </Reveal>

                <MaisonAccordion
                    className="mx-auto max-w-205"
                    entries={faqs.map((item, index) => ({
                        value: `faq-${index}`,
                        question: t(item.question),
                        answer: t(item.answer),
                    }))}
                />
            </Wrap>
        </Section>
    );
}
