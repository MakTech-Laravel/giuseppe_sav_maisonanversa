import { useTranslation } from 'react-i18next';

const ITEMS = [
    'Gebouwd voor generaties',
    'Maison Anversa',
    'European Heritage Sports and Lifestyle House',
    'Heritage No.001',
    'Founding Edition',
    'Antwerpen, België',
    '100 stuks',
    'Elk genummerd',
] as const;

/** Brand names that stay untranslated in every locale. */
const LITERAL = new Set<string>([
    'Maison Anversa',
    'European Heritage Sports and Lifestyle House',
    'Heritage No.001',
    'Founding Edition',
]);

/**
 * The dark ticker under the hero. The track is rendered twice so the CSS
 * animation can travel exactly half its width and land where it started —
 * which is why `marquee-track` lives in the stylesheet rather than in GSAP.
 */
export function HomeMarquee() {
    const { t } = useTranslation();

    const label = (item: (typeof ITEMS)[number]) =>
        LITERAL.has(item) ? item : t(item);

    const row = (
        <>
            {ITEMS.map((item) => (
                <span key={item} className="contents">
                    <span className="px-9 font-sans text-[9px] font-light tracking-[0.3em] text-cream/85 uppercase">
                        {label(item)}
                    </span>
                    <span
                        aria-hidden="true"
                        className="px-0 text-[6px] text-gold/40"
                    >
                        ·
                    </span>
                </span>
            ))}
        </>
    );

    return (
        <div className="overflow-hidden border-y border-gold/20 bg-choc py-3.5 whitespace-nowrap">
            <div className="marquee-track" aria-hidden="true">
                {row}
                {row}
            </div>
            <p className="sr-only">{ITEMS.map(label).join(' · ')}</p>
        </div>
    );
}
