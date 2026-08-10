import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

const BENEFITS = [
    {
        icon: '→',
        title: 'Vroege Toegang',
        desc: 'Als eerste toegang tot alle toekomstige releases — minimaal 48u voor het grote publiek.',
    },
    {
        icon: '◈',
        title: 'Exclusieve Evenementen',
        desc: 'Persoonlijke uitnodigingen voor Maison Anversa events en productpresentaties.',
    },
    {
        icon: '✦',
        title: 'Behind the Scenes',
        desc: 'Persoonlijke updates van de oprichter over het merk en de producten.',
    },
    {
        icon: '⬡',
        title: 'Naamregistratie',
        desc: 'Uw naam in het officiële Founding Circle register — permanent deel van het merkarchief.',
    },
    {
        icon: '◎',
        title: 'Uniek Nummer',
        desc: 'Uw editienummer (001–100) op racket, certificaat en paspoort. Voor altijd.',
    },
    {
        icon: '★',
        title: 'Prioriteit Support',
        desc: 'Alle vragen behandeld als eerste. Reactie binnen 12 uur.',
    },
] as const;

export function HomeCircle() {
    const { t } = useTranslation();

    return (
        <Section tone="cream">
            <Wrap>
                <Reveal className="mx-auto mb-16 max-w-160 text-center">
                    <Eyebrow tone="gold2" className="text-center">
                        {t('De eerste 100')}
                    </Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="mb-5 font-serif text-[clamp(30px,4vw,52px)] leading-[1.1] font-medium">
                        {t('De')}{' '}
                        <em className="text-gold2 italic">Founding Circle</em>
                    </h2>
                    <p className="text-base leading-[1.85] text-choc3">
                        {t(
                            'Niet de eerste 100 klanten. De eerste 100 mensen die geloofden voordat iedereen het wist.',
                        )}
                    </p>
                </Reveal>

                <div className="mb-13 grid gap-0.5 ma-lg:grid-cols-3 md:grid-cols-2">
                    {BENEFITS.map((benefit) => (
                        <Reveal
                            key={benefit.title}
                            className="border border-gold/15 bg-cream2 px-7 py-8 transition-colors hover:border-gold/40"
                        >
                            <span
                                aria-hidden="true"
                                className="mb-4.5 flex size-7.5 items-center justify-center rounded-full border border-gold2 text-[12px] text-gold2"
                            >
                                {benefit.icon}
                            </span>
                            <h3 className="mb-2 font-serif text-xl font-medium text-choc">
                                {t(benefit.title)}
                            </h3>
                            <p className="text-[14px] leading-[1.7] text-choc3">
                                {t(benefit.desc)}
                            </p>
                        </Reveal>
                    ))}
                </div>

                <div className="text-center">
                    <MaisonButton as={MaisonLink} variant="choc" to="circle">
                        {t('Meer over de Founding Circle →')}
                    </MaisonButton>
                </div>
            </Wrap>
        </Section>
    );
}
