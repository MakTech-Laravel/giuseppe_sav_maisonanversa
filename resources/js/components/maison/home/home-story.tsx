import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { Reveal } from '@/components/maison/ui/reveal';
import { TextLink } from '@/components/maison/ui/text-link';

const VALUES = [
    {
        icon: '◈',
        title: 'Europees Erfgoed',
        desc: 'Geïnspireerd door eeuwen Europese cultuur en klassieke waarden.',
    },
    {
        icon: '✦',
        title: 'Vakmanschap',
        desc: 'De meest verfijnde materialen. Minutieuze aandacht voor elk detail.',
    },
    {
        icon: '◎',
        title: 'Gemeenschap',
        desc: 'Mensen samenbrengen die dezelfde waarden delen.',
    },
    {
        icon: '⬡',
        title: 'Gebouwd om te Blijven',
        desc: 'Producten en ervaringen die de tand des tijds doorstaan.',
    },
] as const;

export function HomeStory() {
    const { t } = useTranslation();

    return (
        <div className="grid min-h-120 bg-cream ma-lg:grid-cols-[1fr_1.1fr_1fr] md:grid-cols-2">
            <Reveal className="flex flex-col justify-center border-gold/15 px-8 py-16 md:border-r md:px-13">
                <Eyebrow tone="gold2">{t('Ons Verhaal')}</Eyebrow>
                <h2 className="mt-3 mb-5 font-serif text-[clamp(26px,3vw,38px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                    {t('Geworteld in')}
                    <br />
                    {t('Antwerpen.')}
                    <br />
                    {t('Gebouwd voor')}
                    <br />
                    {t('generaties.')}
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

            <div className="relative flex min-h-60 items-center justify-center overflow-hidden bg-choc2 md:min-h-120">
                <PlaceholderImage
                    asset="atelier-workshop"
                    ratio={null}
                    alt=""
                    captioned={false}
                    overlay="linear-gradient(145deg, rgba(53,39,34,0.82) 0%, rgba(41,28,24,0.92) 100%)"
                    className="absolute inset-0 h-full w-full"
                />
                <div className="relative z-1 flex flex-col items-center gap-1.5">
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
                <div className="absolute inset-x-0 bottom-0 flex items-center gap-3 border-t border-gold/15 bg-gold/8 px-6 py-3">
                    <div className="size-7.5 shrink-0 overflow-hidden rounded-full border border-gold">
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

            <div className="hidden flex-col justify-center gap-6 border-l border-gold/15 px-10 py-10 ma-lg:flex">
                {VALUES.map((value) => (
                    <Reveal
                        key={value.title}
                        variant="left"
                        className="flex items-start gap-3.5"
                    >
                        <span
                            aria-hidden="true"
                            className="mt-0.5 flex size-6.5 shrink-0 items-center justify-center rounded-full border border-gold/35 text-[11px] text-gold2"
                        >
                            {value.icon}
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
