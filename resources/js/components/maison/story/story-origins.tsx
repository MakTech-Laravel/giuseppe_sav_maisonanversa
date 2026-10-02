import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Section, Wrap } from '@/components/maison/ui/section';

export function StoryOrigins() {
    const { t } = useTranslation();

    return (
        <Section tone="cream" className="py-20 md:py-28">
            <Wrap className="mx-auto max-w-3xl text-center">
                <Eyebrow tone="gold2">{t('03 · Hoe het begon')}</Eyebrow>
                <p className="mt-6 text-[15px] leading-[1.9] text-choc3">
                    {t(
                        'Maison Anversa ontstond niet uit een businessplan, maar uit dingen waar ik al jaren van hou.',
                    )}
                </p>
                <h2 className="mt-10 font-serif text-[clamp(28px,4vw,44px)] leading-[1.2] font-medium text-choc">
                    {t('Koffie. Mode. Geschiedenis. Sport.')}
                </h2>
                <p className="mt-8 text-[15px] leading-[1.9] text-choc3">
                    {t(
                        'Lange tijd stonden ze los van elkaar. Tot ik padel ontdekte en me begon af te vragen waarom de wereld rondom sport vaak zo anders voelt dan de wereld daarbuiten.',
                    )}
                </p>
                <p className="mt-10 font-serif text-[clamp(22px,2.5vw,30px)] leading-[1.4] text-choc italic">
                    {t(
                        'Waarom zou stijl stoppen wanneer je de baan opstapt?',
                    )}
                </p>
                <p className="mt-8 text-[15px] leading-[1.9] text-choc3">
                    {t(
                        'Zo ontstond het idee voor Maison Anversa: één huis waarin sport, stijl, cultuur en vakmanschap samenkomen.',
                    )}
                </p>
            </Wrap>
        </Section>
    );
}
