import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Reveal } from '@/components/maison/ui/reveal';
import { TextLink } from '@/components/maison/ui/text-link';

const VALUES = [
    {
        title: 'Europees Erfgoed',
        desc: 'Geïnspireerd door eeuwen Europese cultuur en klassieke waarden.',
    },
    {
        title: 'Vakmanschap',
        desc: 'De meest verfijnde materialen. Minutieuze aandacht voor elk detail.',
    },
    {
        title: 'Gemeenschap',
        desc: 'Mensen samenbrengen die dezelfde waarden delen.',
    },
    {
        title: 'Gebouwd om te Blijven',
        desc: 'Producten en ervaringen die de tand des tijds doorstaan.',
    },
] as const;

export function HomeStory() {
    const { t } = useTranslation();

    return (
        <div className="grid min-h-120 grid-cols-1 bg-cream ma-md:grid-cols-2 ma-lg:grid-cols-[1fr_1.1fr_1fr]">
            <Reveal className="flex flex-col justify-center border-gold/15 px-8 py-16 ma-md:border-r ma-md:px-13">
                <Eyebrow tone="gold2">{t('Ons Verhaal')}</Eyebrow>
                <h2 className="mt-3 mb-5 font-serif text-[clamp(26px,3vw,38px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                    {t('Geworteld in')} {t('Antwerpen.')}
                    <br />
                    {t('Gebouwd voor')} {t('generaties.')}
                </h2>
                <p className="mb-4 text-[15px] leading-[1.85] text-choc3">
                    {t(
                        'Maison Anversa is een Europees erfgoedhuis. Niet een sportmerk. Een huis — met waarden, met verhalen, met producten die mensen bewaren en niet weggooien.',
                    )}
                </p>
                <p className="mb-4 text-[15px] leading-[1.85] text-choc3">
                    {t(
                        'Wij begonnen met padel omdat het de perfecte samenkomst is van alles waarin wij geloven: gemeenschap, performance en de voldoening van iets goed gemaakt.',
                    )}
                </p>
                <TextLink as={MaisonLink} to="story">
                    {t('Lees ons verhaal →')}
                </TextLink>
            </Reveal>

            <div className="relative flex min-h-60 flex-col overflow-hidden bg-choc2 ma-md:min-h-120">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(141,112,90,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(141,112,90,0.04)_1px,transparent_1px)] bg-[size:48px_48px]"
                />
                <div className="relative z-1 flex flex-1 flex-col items-center justify-center gap-2 px-6">
                    <PlaceholderImage
                        asset="logo-icon"
                        ratio="1 / 1"
                        alt="Maison Anversa"
                        captioned={false}
                        className="size-11 opacity-70"
                    />
                    <div className="font-serif text-[14px] tracking-[0.28em] text-sand uppercase">
                        Maison Anversa
                    </div>
                </div>
                <div className="relative z-1 flex items-center gap-3 border-t border-gold/20 px-6 py-3.5">
                    <div className="flex size-7.5 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gold/50">
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

            <div className="flex flex-col justify-center gap-6 border-gold/15 px-8 py-12 ma-md:col-span-2 ma-md:grid ma-md:grid-cols-2 ma-md:gap-x-10 ma-md:gap-y-6 ma-md:border-t ma-md:px-13 ma-lg:col-span-1 ma-lg:flex ma-lg:border-t-0 ma-lg:border-l ma-lg:px-10 ma-lg:py-10">
                {VALUES.map((value) => (
                    <Reveal
                        key={value.title}
                        variant="left"
                        className="flex items-start gap-3.5"
                    >
                        <span
                            aria-hidden="true"
                            className="mt-0.5 flex size-6.5 shrink-0 items-center justify-center rounded-full border border-gold/35 text-[12px] leading-none text-gold2"
                        >
                            +
                        </span>
                        <div>
                            <div className="mb-0.75 font-sans text-[9px] font-medium tracking-[0.22em] text-choc uppercase">
                                {t(value.title)}
                            </div>
                            <div className="text-[13px] leading-[1.6] text-choc3">
                                {t(value.desc)}
                            </div>
                        </div>
                    </Reveal>
                ))}
            </div>
        </div>
    );
}
