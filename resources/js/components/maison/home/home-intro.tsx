import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

export function HomeIntro() {
    const { t } = useTranslation();

    return (
        <Section tone="cream" className="py-20 text-center">
            <Wrap>
                <Eyebrow tone="gold2" className="text-center">
                    {t('Why we exist')}
                </Eyebrow>
                <GoldRule center className="mx-auto" />
                <Reveal>
                    <p className="mx-auto max-w-205 font-serif text-[clamp(24px,3vw,42px)] leading-[1.5] text-choc">
                        {t('The world does not need more products.')}
                        <br />
                        {t('It needs more')}{' '}
                        <em className="text-gold2 italic">{t('meaning')}</em>{' '}
                        {t('.')}
                    </p>
                </Reveal>
            </Wrap>
        </Section>
    );
}
