import { useTranslation } from 'react-i18next';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryClosing() {
    const { t } = useTranslation();

    return (
        <Section tone="cream" className="border-t border-gold/15 py-16 md:py-20">
            <Wrap className="text-center">
                <p className="font-serif text-[clamp(18px,2vw,24px)] leading-[1.5] text-choc italic">
                    Every legacy begins with a first chapter.
                </p>
                <p className="mt-4 font-sans text-[10px] tracking-[0.3em] text-gold2 uppercase">
                    {t('Antwerpen, België · Est. 2026')}
                </p>
            </Wrap>
        </Section>
    );
}
