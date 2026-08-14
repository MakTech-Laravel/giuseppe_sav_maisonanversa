import { useTranslation } from 'react-i18next';
import { CirclePortal } from '@/components/maison/circle/circle-portal';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
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
            <MaisonSeoHead page="circle" />

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
                    <Reveal className="mx-auto mb-16 max-w-160 text-center">
                        <h2 className="mb-5 font-serif text-[clamp(30px,4vw,52px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                            {t('Jij was er bij het')} <em>{t('begin')}</em>
                        </h2>
                        <p className="text-base leading-[1.85] text-choc3">
                            {t(
                                'De Founding Circle is de permanente gemeenschap van iedereen die Heritage No.001 koopt. Uw lidmaatschap is levenslang. Uw nummer is van u — voor altijd. Dit is geen loyaliteitsprogramma. Dit is een erkenning.',
                            )}
                        </p>
                    </Reveal>

                    {/*
                     * Prototype `.fc-full-benefits`: 2px sand gutters read as
                     * hairlines between cream cells, with a faint watermark
                     * number behind each benefit.
                     */}
                    <div className="mb-16 grid grid-cols-1 gap-0.5 bg-sand md:grid-cols-2">
                        {BENEFITS.map((benefit) => (
                            <Reveal
                                key={benefit.num}
                                className="relative overflow-hidden bg-cream p-6 md:p-10"
                            >
                                <span
                                    aria-hidden="true"
                                    className="pointer-events-none absolute -top-2.5 -right-2.5 font-serif text-[80px] leading-none font-bold text-gold/4"
                                >
                                    {benefit.num}
                                </span>
                                <div className="relative mb-3.5 font-serif text-[36px] leading-none font-light text-gold/25">
                                    {benefit.num}
                                </div>
                                <h3 className="relative mb-2 font-serif text-[22px] font-medium text-choc">
                                    {benefit.title === 'Behind the Scenes'
                                        ? benefit.title
                                        : t(benefit.title)}
                                </h3>
                                <p className="relative text-[15px] leading-[1.7] text-choc3">
                                    {t(benefit.desc)}
                                </p>
                            </Reveal>
                        ))}
                    </div>

                    <CirclePortal />

                    <div className="mt-10 text-center">
                        <MaisonButton
                            variant="choc"
                            onClick={openOrder}
                            className="max-w-full px-4 text-center whitespace-normal"
                        >
                            {t('Word Founding Member — Bekijk Heritage No.001')}
                        </MaisonButton>
                    </div>
                </Wrap>
            </Section>
        </>
    );
}
