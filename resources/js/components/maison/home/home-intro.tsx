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
                    {t('Waarom wij bestaan')}
                </Eyebrow>
                <GoldRule center className="mx-auto" />
                <Reveal>
                    <p className="mx-auto max-w-205 font-serif text-[clamp(24px,3vw,42px)] leading-[1.5] text-choc">
                        {t('De wereld heeft geen extra producten nodig.')}
                        <br />
                        {t('Zij heeft meer')}{' '}
                        <em className="text-gold2 italic">{t('betekenis')}</em>{' '}
                        {t('nodig.')}
                    </p>
                </Reveal>
            </Wrap>
        </Section>
    );
}
