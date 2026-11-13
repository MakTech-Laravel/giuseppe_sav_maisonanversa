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
        title: 'Early Access',
        desc: 'First access to all future releases — at least 48h before the general public.',
    },
    {
        icon: '◈',
        title: 'Exclusive Events',
        desc: 'Personal invitations to Maison Anversa events and product presentations.',
    },
    {
        icon: '✦',
        title: 'Behind the Scenes',
        desc: 'Personal updates from the founder about the brand and products.',
    },
    {
        icon: '⬡',
        title: 'Name Registration',
        desc: 'Your name in the official Founding Circle register — a permanent part of the brand archive.',
    },
    {
        icon: '◎',
        title: 'Unique Number',
        desc: 'Your edition number (001–100) on racket, certificate and passport. Forever.',
    },
    {
        icon: '★',
        title: 'Priority Support',
        desc: 'All questions handled first. Response within 12 hours.',
    },
] as const;

export function HomeCircle() {
    const { t } = useTranslation();

    return (
        <Section tone="cream">
            <Wrap>
                <Reveal className="mx-auto mb-16 max-w-160 text-center">
                    <Eyebrow tone="gold2" className="text-center">
                        {t('The first 100')}
                    </Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="mb-5 font-serif text-[clamp(30px,4vw,52px)] leading-[1.1] font-medium">
                        {t('The')}{' '}
                        <em className="text-gold2 italic">Founding Circle</em>
                    </h2>
                    <p className="text-base leading-[1.85] text-choc3">
                        {t(
                            'Not the first 100 customers. The first 100 people who believed before everyone knew.',
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
                        {t('More about the Founding Circle →')}
                    </MaisonButton>
                </div>
            </Wrap>
        </Section>
    );
}
