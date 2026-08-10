import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { ImageAssetName } from '@/lib/imagery';

type JournalEntry = {
    asset: ImageAssetName;
    category: string;
    title: string;
    excerpt: string;
    meta: string;
};

const ENTRIES: readonly JournalEntry[] = [
    {
        asset: 'antwerp-cityscape',
        category: 'Erfgoed',
        title: 'Waarom Antwerpen de meest ondervertegenwoordigde stad in de luxewereld is',
        excerpt:
            'Een stad met eeuwen van diamantambacht, Vlaamse Meesters en culturele ambitie — en toch geen enkel wereldmerk dat het draagt.',
        meta: 'Yusuf Savran · Juni 2026',
    },
    {
        asset: 'heritage-001-lifestyle-court',
        category: 'Sport & Cultuur',
        title: 'Waarom padel het meest sociale sport ter wereld is — en wat dat betekent voor gemeenschap',
        excerpt:
            'Padel is geen tennis. Het is een ritueel. Een manier van samenkomen die andere sporten niet kennen.',
        meta: 'Maison Anversa · Mei 2026',
    },
    {
        asset: 'atelier-workshop',
        category: 'Design',
        title: 'Quiet Luxury — wat het is, waarom het groeit, en waarom het perfect is voor sport',
        excerpt:
            "De beweging weg van logo's naar kwaliteit is niet tijdelijk. Het is een fundamentele verschuiving in hoe mensen luxe definiëren.",
        meta: 'Maison Anversa · April 2026',
    },
    {
        asset: 'heritage-001-detail-gravure',
        category: 'Vakmanschap',
        title: '3K Carbon — waarom wij kozen voor de meest veeleisende weave in de industrie',
        excerpt:
            'Niet elke carbon is gelijk. De keuze voor 3K carbon bij Heritage No.001 was een bewuste — en dure — beslissing.',
        meta: 'Maison Anversa · Maart 2026',
    },
    {
        asset: 'heritage-001-front',
        category: 'Founding Circle',
        title: 'Wat het betekent om nummer 001 te zijn — over de Founding Circle en de eerste 100',
        excerpt:
            'Er is iets bijzonders aan de mensen die ergens in geloven voor de rest het weet. De Founding Circle is voor hen.',
        meta: 'Yusuf Savran · Februari 2026',
    },
    {
        asset: 'hero-mansion',
        category: 'Materialen',
        title: 'De terugkeer van leder in sport — hoe een traditioneel materiaal de toekomst definieert',
        excerpt:
            'Synthetische grepen domineren de markt. Wij kozen voor echt leder. Niet voor nostalgie — maar voor kwaliteit.',
        meta: 'Maison Anversa · Januari 2026',
    },
] as const;

export default function Journal() {
    const { t } = useTranslation();

    return (
        <>
            <Head title="Journal" />

            <PageHero
                eyebrow="Maison Anversa"
                title={
                    <>
                        {t('Het')} <em>Journal</em>
                    </>
                }
                subtitle={t(
                    'Verhalen over sport, cultuur, design en het leven. Vanuit de wereld van Maison Anversa.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <div className="grid gap-10 ma-lg:grid-cols-3 md:grid-cols-2">
                        {ENTRIES.map((entry) => (
                            <Reveal key={entry.title} className="group">
                                <div className="relative mb-4 aspect-4/3 overflow-hidden bg-choc2">
                                    <PlaceholderImage
                                        asset={entry.asset}
                                        ratio={null}
                                        alt={t(entry.title)}
                                        captioned={false}
                                        overlay="linear-gradient(to top, rgba(41,28,24,0.5), rgba(41,28,24,0.1))"
                                        className="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-[1.03]"
                                    />
                                </div>
                                <span className="mb-2 block font-sans text-[9px] tracking-[0.22em] text-gold2 uppercase">
                                    {entry.category === 'Founding Circle' ||
                                    entry.category === 'Design'
                                        ? entry.category
                                        : t(entry.category)}
                                </span>
                                <h2 className="mb-2 font-serif text-[22px] leading-[1.25] font-medium text-choc">
                                    {t(entry.title)}
                                </h2>
                                <p className="mb-3 text-[14px] leading-[1.7] text-choc3">
                                    {t(entry.excerpt)}
                                </p>
                                <p className="font-sans text-[10px] tracking-[0.12em] text-stone uppercase">
                                    {entry.meta}
                                </p>
                            </Reveal>
                        ))}
                    </div>
                </Wrap>
            </Section>
        </>
    );
}
