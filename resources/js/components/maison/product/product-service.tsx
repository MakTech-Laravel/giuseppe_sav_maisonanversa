import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

const CARDS = [
    {
        icon: '◆',
        title: 'Verzending',
        body: 'Gratis verzekerd verzonden binnen de Benelux. EU-levering in 3–5 werkdagen. Elk pakket handmatig gecontroleerd en verzegeld.',
    },
    {
        icon: '↺',
        title: '30 dagen retour',
        body: 'Niet overtuigd? Retour binnen 30 dagen, mits ongebruikt. Volledige terugbetaling, zonder vragen.',
    },
    {
        icon: '◇',
        title: 'Veilig reserveren',
        body: 'Uw nummer wordt vastgelegd na bevestiging. De Founding Edition wordt in één beperkte productieronde vervaardigd — volledig transparant.',
    },
] as const;

export function ProductService() {
    const { t } = useTranslation();

    return (
        <Section tone="cream2">
            <Wrap>
                <Reveal className="mb-13 text-center">
                    <Eyebrow tone="gold2">{t('Service & Veiligheid')}</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium tracking-[0.04em] uppercase">
                        {t('Met zorg geleverd.')}
                    </h2>
                    <p className="mt-3.5 font-sans text-xs text-stone">
                        {t(
                            'Voorgenomen servicebeleid — definitief bij lancering.',
                        )}
                    </p>
                </Reveal>

                <div className="grid gap-px bg-gold/20 md:grid-cols-3">
                    {CARDS.map((card) => (
                        <Reveal
                            key={card.title}
                            className="bg-cream px-8.5 py-11"
                        >
                            <div
                                aria-hidden="true"
                                className="mb-4 text-2xl text-gold2"
                            >
                                {card.icon}
                            </div>
                            <h3 className="mb-3 font-serif text-[21px] text-choc">
                                {t(card.title)}
                            </h3>
                            <p className="text-[13px] leading-[1.75] text-choc3">
                                {t(card.body)}
                            </p>
                        </Reveal>
                    ))}
                </div>
            </Wrap>
        </Section>
    );
}
