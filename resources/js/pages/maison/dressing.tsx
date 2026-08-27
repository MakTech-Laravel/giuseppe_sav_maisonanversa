import { useTranslation } from 'react-i18next';
import { DressingItemMedia } from '@/components/maison/dressing/dressing-item-media';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl, maisonUrl } from '@/lib/maison-navigation';

const VALUES = [
    {
        icon: '◇',
        title: 'Technische stoffen',
        desc: 'Premium Italiaanse mills en technische weefsels die presteren op de baan en eruitzien als burgerkleding ernaast.',
    },
    {
        icon: '◆',
        title: 'Beperkte productie',
        desc: 'Kleinere oplages. Elk seizoen een select aantal stukken. Niet meer, niet minder.',
    },
    {
        icon: '◈',
        title: 'Tijdloze palet',
        desc: 'Chocoladebruin, antiek goud, vintage crème en zwart. Dezelfde taal als het racket.',
    },
] as const;

type DressingItem = {
    name: string;
    slug: string;
    category: string;
    status: 'coming_soon' | 'available';
    image_url: string | null;
    image_key: string | null;
};

export default function Dressing({ items = [] }: { items?: DressingItem[] }) {
    const { t } = useTranslation();
    const { openNewsletter } = useShellActions();
    const { locale } = useLocale();
    const statusLabel = (status: DressingItem['status']) =>
        status === 'available' ? t('Beschikbaar') : t('Binnenkort');

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Kamer III · Maison Anversa')}
                title={
                    <>
                        {t('De')} <em>{t('Kleedkamer')}</em>
                    </>
                }
                subtitle={t(
                    "Activewear en accessoires voor de padelspeler die waarde hecht aan stille luxe. Elk stuk is ontworpen met dezelfde filosofie als Heritage No.001 — materialen eerst, logo's nooit.",
                )}
            />

            <Section tone="dark">
                <Wrap className="max-w-160 text-center">
                    <Eyebrow>{t('Binnenkort')}</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="mb-5 font-serif text-[clamp(28px,4vw,42px)] leading-[1.15] font-medium [&_em]:text-gold [&_em]:italic">
                        {t('De collectie wordt')}
                        <br />
                        {t('op dit moment')} <em>{t('ontworpen')}</em>
                    </h2>
                    <p className="mb-8 text-base leading-[1.85] text-sand">
                        {t(
                            'Onze activewear verschijnt in een beperkte eerste editie, gelijktijdig met de levering van Heritage No.001 in Q1 2027. Leden van de Founding Circle krijgen als eerste toegang.',
                        )}
                    </p>
                    <MaisonButton
                        variant="filled"
                        block
                        onClick={openNewsletter}
                    >
                        {t('Schrijf in voor Heritage Letter')}
                    </MaisonButton>
                </Wrap>
            </Section>

            {/*
             * The prototype marks this block `story-grid`, which has no CSS —
             * so philosophy, the dressing photograph and the three values
             * stack in source order rather than sitting in the home page's
             * three columns.
             */}
            <Section tone="cream">
                <Wrap>
                    <Reveal className="max-w-160 py-4 ma-md:py-8">
                        <Eyebrow tone="gold2">{t('De Filosofie')}</Eyebrow>
                        <GoldRule />
                        <h2 className="mt-3 mb-5 font-serif text-[clamp(26px,3vw,38px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                            {t('Kleding die')}
                            <br />
                            {t('fluistert.')}
                        </h2>
                        <p className="text-[15px] leading-[1.85] text-stone">
                            {t(
                                "Geen opvallende logo's. Geen neon. Geen trends die volgende seizoenen verdwijnen. De Kleedkamer is voor de speler die er verzorgd wil uitzien op en naast de baan — zonder ooit te schreeuwen.",
                            )}
                        </p>
                    </Reveal>

                    <div className="relative flex min-h-120 flex-col overflow-hidden bg-choc2">
                        <PlaceholderImage
                            asset="room-dressing"
                            ratio={null}
                            alt={t('Kleedkamer')}
                            captioned={false}
                            overlay="linear-gradient(145deg, rgba(53,39,34,0.82) 0%, rgba(41,28,24,0.92) 100%)"
                            className="absolute inset-0 h-full w-full"
                        />
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(141,112,90,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(141,112,90,0.03)_1px,transparent_1px)] bg-size-[40px_40px]"
                        />
                        <div className="relative z-1 flex flex-1 flex-col items-center justify-center gap-1.5">
                            <div className="opacity-[0.08]">
                                <PlaceholderImage
                                    asset="logo-icon"
                                    ratio="1 / 1"
                                    alt="Maison Anversa"
                                    captioned={false}
                                    className="size-10 mix-blend-screen"
                                />
                            </div>
                            <div className="font-serif text-[14px] tracking-[0.25em] text-cream/35 uppercase">
                                Maison Anversa
                            </div>
                        </div>
                        <div className="relative z-1 flex items-center gap-3 border-t border-gold/15 bg-gold/8 px-6 py-3">
                            <div className="flex size-7.5 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gold">
                                <PlaceholderImage
                                    asset="logo-icon"
                                    ratio="1 / 1"
                                    alt=""
                                    captioned={false}
                                    className="size-full"
                                />
                            </div>
                            <p className="font-sans text-[8px] leading-[1.6] tracking-[0.2em] text-sand uppercase">
                                European Heritage Sports and Lifestyle House
                                <br />
                                Antwerp · Belgium · Est. 2026
                            </p>
                        </div>
                    </div>

                    <div className="flex max-w-160 flex-col gap-6 py-10">
                        {VALUES.map((value) => (
                            <Reveal
                                key={value.title}
                                variant="left"
                                className="flex items-start gap-3.5"
                            >
                                <span
                                    aria-hidden="true"
                                    className="mt-0.5 flex size-6.5 shrink-0 items-center justify-center rounded-full border border-gold/35 text-[11px] leading-none text-gold2"
                                >
                                    {value.icon}
                                </span>
                                <div>
                                    <h3 className="mb-0.75 font-sans text-[9px] font-medium tracking-[0.22em] text-choc uppercase">
                                        {t(value.title)}
                                    </h3>
                                    <p className="text-[13px] leading-[1.6] text-choc3">
                                        {t(value.desc)}
                                    </p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </Wrap>
            </Section>

            <Section tone="dark">
                <Wrap>
                    <Reveal className="mb-12 text-center">
                        <Eyebrow>{t('De Eerste Collectie')}</Eyebrow>
                        <GoldRule center className="mx-auto" />
                        <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium [&_em]:text-gold [&_em]:italic">
                            {t('Voor')} <em>{t('op weg')}</em>{' '}
                            {t('naar de baan')}
                        </h2>
                    </Reveal>

                    <div className="grid grid-cols-2 gap-0.5 ma-lg:grid-cols-6 md:grid-cols-3">
                        {items.map((item) => (
                            <Reveal key={item.slug}>
                                <MaisonLink
                                    href={`${maisonUrl('dressing', locale)}/${item.slug}`}
                                    className="group block h-full border border-gold/10 bg-white/3 transition-colors hover:border-gold/25 hover:bg-gold/5"
                                >
                                    <div className="relative aspect-square overflow-hidden bg-choc2">
                                        <DressingItemMedia
                                            imageUrl={item.image_url}
                                            imageKey={item.image_key}
                                            alt={item.name}
                                            className="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-[1.03]"
                                        />
                                        <span className="absolute top-2 left-2 rounded-full bg-choc/80 px-2 py-1 font-sans text-[8px] tracking-[0.18em] text-gold uppercase">
                                            {statusLabel(item.status)}
                                        </span>
                                    </div>
                                    <div className="px-4 pt-4 pb-6">
                                        <h3 className="mb-1.5 font-sans text-[9px] font-medium tracking-[0.2em] text-gold uppercase">
                                            {item.name}
                                        </h3>
                                        <p className="text-[11px] tracking-[0.1em] text-sand uppercase">
                                            {item.category}
                                        </p>
                                    </div>
                                </MaisonLink>
                            </Reveal>
                        ))}
                    </div>
                </Wrap>
            </Section>

            <Section tone="cream" className="text-center">
                <Wrap className="max-w-140">
                    <Eyebrow tone="gold2">Founding Circle</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="mb-5 font-serif text-[clamp(28px,3.5vw,42px)] font-medium [&_em]:text-gold2 [&_em]:italic">
                        {t('Wees de eerste')}
                        <br />
                        {t('die het')} <em>{t('ziet')}</em>
                    </h2>
                    <p className="mb-7 text-[15px] leading-[1.85] text-stone">
                        {t(
                            'Founding Circle leden krijgen 48 uur exclusieve toegang tot de Kleedkamer collectie vóór de openbare release.',
                        )}
                    </p>
                    <MaisonButton
                        as={MaisonLink}
                        variant="choc"
                        href={foundingProductUrl(locale)}
                    >
                        {t('Ontdek Heritage No.001 →')}
                    </MaisonButton>
                </Wrap>
            </Section>
        </>
    );
}
