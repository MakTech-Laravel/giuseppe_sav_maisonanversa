import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { CirclePortal } from '@/components/maison/circle/circle-portal';
import { MaisonLink } from '@/components/maison/maison-link';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

const BENEFITS = [
    {
        num: '01',
        title: 'Vroege Toegang',
        desc: 'Als eerste toegang tot alle toekomstige releases en collecties van Maison Anversa — minimaal 48 uur voor het grote publiek.',
    },
    {
        num: '02',
        title: 'Exclusieve Evenementen',
        desc: 'Persoonlijke uitnodigingen voor Maison Anversa events: productpresentaties, padel sessies, clubavonden en pop-up evenementen in Europa.',
    },
    {
        num: '03',
        title: 'Behind the Scenes',
        desc: 'Persoonlijke updates van de oprichter over het merk: productontwikkeling, nieuwe collecties, reizen voor leveranciers, beslissingen achter de schermen.',
    },
    {
        num: '04',
        title: 'Naamregistratie',
        desc: 'Uw naam wordt opgenomen in het officiële Founding Circle register — een document dat bij het merk bewaard wordt voor altijd.',
    },
    {
        num: '05',
        title: 'Uniek Editienummer',
        desc: 'Uw editienummer (001–100) staat op het racket, het certificaat en het paspoort. Van u. Voor altijd.',
    },
    {
        num: '06',
        title: 'Prioriteit Support',
        desc: 'Alle vragen en verzoeken van Founding Members worden altijd als eerste behandeld. Reactietijd binnen 12 uur.',
    },
] as const;

export default function Circle() {
    const { t } = useTranslation();
    const { openOrder } = useShellActions();

    return (
        <>
            <Head title="Founding Circle" />

            <PageHero
                eyebrow={t('De eerste 100')}
                title={
                    <>
                        {t('De')} <em>Founding Circle</em>
                    </>
                }
                subtitle={t(
                    'Niet de eerste 100 klanten. De eerste 100 mensen die geloofden.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <Reveal className="mx-auto mb-14 max-w-160 text-center">
                        <h2 className="mb-5 font-serif text-[clamp(28px,3.5vw,42px)] font-medium [&_em]:text-gold2 [&_em]:italic">
                            {t('Jij was er bij het')} <em>{t('begin')}</em>
                        </h2>
                        <p className="text-base leading-[1.85] text-choc3">
                            {t(
                                'De Founding Circle is de permanente gemeenschap van iedereen die Heritage No.001 koopt. Uw lidmaatschap is levenslang. Uw nummer is van u — voor altijd. Dit is geen loyaliteitsprogramma. Dit is een erkenning.',
                            )}
                        </p>
                    </Reveal>

                    <div className="mb-16 grid gap-0.5 md:grid-cols-2">
                        {BENEFITS.map((benefit) => (
                            <Reveal
                                key={benefit.num}
                                className="border border-gold/15 bg-cream2 px-7 py-8"
                            >
                                <div className="mb-3 font-serif text-[13px] tracking-[0.2em] text-gold2">
                                    {benefit.num}
                                </div>
                                <h3 className="mb-2 font-serif text-xl font-medium text-choc">
                                    {benefit.title === 'Behind the Scenes'
                                        ? benefit.title
                                        : t(benefit.title)}
                                </h3>
                                <p className="text-[14px] leading-[1.7] text-choc3">
                                    {t(benefit.desc)}
                                </p>
                            </Reveal>
                        ))}
                    </div>
                </Wrap>
            </Section>

            <Section tone="choc2" className="text-cream">
                <Wrap>
                    <CirclePortal />
                </Wrap>
            </Section>

            <Section tone="cream">
                <Wrap className="text-center">
                    <MaisonButton variant="choc" onClick={openOrder}>
                        {t('Word Founding Member — Bekijk Heritage No.001')}
                    </MaisonButton>
                    <p className="mt-4 font-sans text-sm text-choc3">
                        <MaisonLink
                            to="product"
                            className="underline underline-offset-2 hover:text-choc"
                        >
                            Heritage No.001
                        </MaisonLink>
                    </p>
                </Wrap>
            </Section>
        </>
    );
}
