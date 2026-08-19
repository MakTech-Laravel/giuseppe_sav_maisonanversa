import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { FOOTER_COLUMNS, isNavItem } from '@/lib/maison-navigation';

/**
 * Rendered once by the layout.
 *
 * The prototype moved a single `<footer>` element into the active page on every
 * navigation with `appendChild`, which React would fight over — and which put
 * the footer inside the page's own top padding. Living in the layout, it is
 * simply always there.
 */
export function SiteFooter({ onNewsletter }: { onNewsletter: () => void }) {
    const { t } = useTranslation();

    return (
        <footer className="relative overflow-hidden border-t border-gold/12 bg-choc px-6 pt-12 pb-25 md:px-20 md:pt-15 md:pb-9">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-0 bottom-0 h-50 bg-[radial-gradient(ellipse_at_50%_100%,rgba(141,112,90,0.03)_0%,transparent_60%)]"
            />

            <div className="relative mb-7 grid grid-cols-1 gap-9 border-b border-gold/8 pb-11 ma-md:grid-cols-2 ma-md:gap-8 ma-lg:grid-cols-[2fr_1fr_1fr_1fr] ma-lg:gap-12">
                <div className="flex flex-col items-start gap-1.5 ma-md:col-span-2 ma-lg:col-span-1">
                    <div className="mb-1 size-8 overflow-hidden">
                        <PlaceholderImage
                            asset="logo-icon"
                            ratio={null}
                            alt="Maison Anversa"
                            captioned={false}
                            className="size-full [&_img]:object-cover"
                        />
                    </div>

                    <span className="font-serif text-base font-medium tracking-[0.2em] text-cream uppercase">
                        Maison Anversa
                    </span>

                    <span className="mb-3 font-sans text-[8px] tracking-[0.3em] text-gold uppercase">
                        European Heritage Sports and Lifestyle House
                    </span>

                    <p className="max-w-65 text-[13px] leading-[1.7] text-stone">
                        {t(
                            'Een Europees erfgoedhuis geworteld in Antwerpen. Wij bouwen producten voor mensen die begrijpen dat de mooiste dingen in het leven tijd kosten.',
                        )}
                    </p>
                </div>

                {FOOTER_COLUMNS.map((column) => (
                    <div key={column.heading}>
                        <p className="mb-4.5 font-sans text-[8px] font-medium tracking-[0.3em] text-gold uppercase">
                            {t(column.heading)}
                        </p>

                        <ul className="flex flex-col gap-2.5">
                            {column.items.map((item) => (
                                <li key={item.label}>
                                    {isNavItem(item) ? (
                                        <MaisonLink
                                            to={item.page}
                                            hash={item.hash}
                                            className="flex min-h-11 items-center py-1.5 font-sans text-[11px] font-light tracking-[0.08em] text-stone transition-colors hover:text-cream md:min-h-0 md:py-0"
                                        >
                                            {t(item.label)}
                                        </MaisonLink>
                                    ) : item.label === 'Heritage Letter' ? (
                                        <button
                                            type="button"
                                            onClick={onNewsletter}
                                            className="flex min-h-11 items-center py-1.5 font-sans text-[11px] font-light tracking-[0.08em] text-stone transition-colors hover:text-cream md:min-h-0 md:py-0"
                                        >
                                            {t('Heritage Letter')}
                                        </button>
                                    ) : (
                                        <a
                                            href={item.href}
                                            target={
                                                item.href.startsWith('http')
                                                    ? '_blank'
                                                    : undefined
                                            }
                                            rel={
                                                item.href.startsWith('http')
                                                    ? 'noopener noreferrer'
                                                    : undefined
                                            }
                                            className="flex min-h-11 items-center py-1.5 font-sans text-[11px] font-light tracking-[0.08em] text-stone transition-colors hover:text-cream md:min-h-0 md:py-0"
                                        >
                                            {t(item.label)}
                                        </a>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>

            <div className="relative flex flex-col items-start justify-between gap-2 pt-6 md:flex-row md:items-center md:pt-0">
                <p className="font-sans text-[10px] font-light tracking-[0.12em] text-stone/50 md:text-[9px]">
                    {t('© 2026 Maison Anversa. Alle rechten voorbehouden.')}
                </p>

                <p className="font-sans text-[10px] font-light tracking-[0.12em] text-stone/50 md:text-[9px]">
                    {t('Antwerpen, België · maisonanversa.com')}
                </p>
            </div>
        </footer>
    );
}
