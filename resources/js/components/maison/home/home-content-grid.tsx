import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Reveal } from '@/components/maison/ui/reveal';
import type { ImageAssetName } from '@/lib/imagery';
import type { MaisonPage } from '@/lib/maison-navigation';

const CARDS = [
    {
        to: 'story' as MaisonPage,
        asset: 'atelier-workshop' as ImageAssetName,
        eye: 'Het Manifesto',
        title: 'Onze overtuigingen. Onze belofte. Onze richting.',
        desc: 'Wij bouwen geen sportmerk. Wij bouwen een thuis voor mensen die dezelfde waarden delen.',
        link: 'Lees het Manifesto →',
    },
    {
        to: 'journal' as MaisonPage,
        asset: 'heritage-001-lifestyle-court' as ImageAssetName,
        eye: 'Journal',
        title: 'Verhalen over sport, cultuur, design en het leven.',
        desc: 'Het Journal van Maison Anversa — verhalen die de wereld verklaren vanuit onze visie op erfgoed.',
        link: 'Verken het Journal →',
    },
    {
        to: 'corner' as MaisonPage,
        asset: 'heritage-001-detail-gravure' as ImageAssetName,
        eye: 'Club Corner',
        title: 'Maison Anversa in uw padelclub.',
        desc: 'Een fysieke aanwezigheid in geselecteerde padelclubs — product, verhaal en community op één plek.',
        link: 'Ontdek Club Corner →',
    },
    {
        to: 'story' as MaisonPage,
        asset: 'heritage-001-front' as ImageAssetName,
        eye: 'De Oprichter',
        title: 'De visie. Het verhaal. De missie.',
        desc: 'Yusuf Savran richtte Maison Anversa op vanuit een overtuiging — niet vanuit een businessplan.',
        link: 'Ontmoet Yusuf →',
    },
] as const;

/**
 * Four editorial doorways under the pre-order band. Real links rather than
 * click handlers on a div, so the destinations are crawlable and middle-click
 * works — the prototype's `onclick="showPage(...)"` on a div did neither.
 */
export function HomeContentGrid() {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-1 bg-choc text-cream ma-md:grid-cols-2 ma-lg:grid-cols-4">
            {CARDS.map((card) => (
                <Reveal key={card.link} as="div">
                    <MaisonLink
                        to={card.to}
                        className="group flex min-h-80 flex-col border-b border-gold/8 px-9 py-12 transition-colors hover:bg-gold/4 ma-md:border-r ma-lg:border-b-0 ma-lg:last:border-r-0"
                    >
                        <div className="-mx-9 -mt-12 mb-6 h-40 overflow-hidden">
                            <PlaceholderImage
                                asset={card.asset}
                                ratio={null}
                                alt={t(card.title)}
                                captioned={false}
                                overlay="linear-gradient(to top, rgba(41,28,24,0.55) 0%, rgba(41,28,24,0.15) 100%)"
                                className="h-full w-full [&_img]:h-full [&_img]:w-full [&_img]:object-cover [&_img]:object-center"
                            />
                        </div>
                        <div className="mb-1.5 font-sans text-[9px] font-medium tracking-[0.28em] text-gold uppercase">
                            {card.eye === 'Journal' ||
                            card.eye === 'Club Corner'
                                ? card.eye
                                : t(card.eye)}
                        </div>
                        <h2 className="mb-2.5 font-serif text-xl leading-[1.3] font-medium text-cream">
                            {t(card.title)}
                        </h2>
                        <p className="flex-1 text-[13px] leading-[1.7] text-stone">
                            {t(card.desc)}
                        </p>
                        <div className="mt-5 inline-flex items-center gap-2 border-b border-gold/25 pb-0.5 font-sans text-[8px] tracking-[0.25em] text-gold uppercase transition-colors group-hover:border-gold">
                            {t(card.link)}
                        </div>
                    </MaisonLink>
                </Reveal>
            ))}
        </div>
    );
}
