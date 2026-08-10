import { useTranslation } from 'react-i18next';

const ITEMS = [
    'Maison Anversa',
    'European Heritage Sports and Lifestyle House',
    'Heritage No.001',
    'Founding Edition',
    'Antwerpen, België',
    '100 stuks',
    'Elk genummerd',
    'Gebouwd voor generaties',
] as const;

/**
 * The gold ticker under the hero. The track is rendered twice so the CSS
 * animation can travel exactly half its width and land where it started —
 * which is why `marquee-track` lives in the stylesheet rather than in GSAP.
 */
export function HomeMarquee() {
    const { t } = useTranslation();

    const row = (
        <>
            {ITEMS.map((item) => (
                <span key={item} className="contents">
                    <span className="px-9 font-sans text-[9px] font-light tracking-[0.3em] text-gold uppercase">
                        {item === 'Founding Edition' ||
                        item === 'Maison Anversa' ||
                        item === 'Heritage No.001' ||
                        item === 'European Heritage Sports and Lifestyle House'
                            ? item
                            : t(item)}
                    </span>
                    <span
                        aria-hidden="true"
                        className="px-0 text-[6px] text-gold/30"
                    >
                        ·
                    </span>
                </span>
            ))}
        </>
    );

    return (
        <div className="overflow-hidden border-y border-gold/15 bg-gold/8 [mask-image:linear-gradient(to_right,transparent_0%,#000_6%,#000_94%,transparent_100%)] py-3.5 whitespace-nowrap">
            <div className="marquee-track" aria-hidden="true">
                {row}
                {row}
            </div>
            <p className="sr-only">
                {ITEMS.map((item) =>
                    item === 'Founding Edition' ||
                    item === 'Maison Anversa' ||
                    item === 'Heritage No.001' ||
                    item === 'European Heritage Sports and Lifestyle House'
                        ? item
                        : t(item),
                ).join(' · ')}
            </p>
        </div>
    );
}
