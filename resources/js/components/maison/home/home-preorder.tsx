import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Reveal } from '@/components/maison/ui/reveal';
import { Wrap } from '@/components/maison/ui/section';
import { useCheckoutDisplay } from '@/hooks/use-checkout-display';
import { usePurchasesPaused } from '@/hooks/use-purchases-paused';
import type { Edition } from '@/types/edition';

const DEFAULT_INCLUDES = [
    'Heritage Nº 001 racket (individually numbered)',
    "Heritage Certificate with founder's seal",
    "Heritage Passport (24 pages)",
    "Welcome Card & Founder's Letter",
    'Founding Circle invitation',
    'Premium canvas stofdoek',
] as const;

/**
 * The pre-order band. The bar and the count animate once when the section
 * scrolls into view, reading from the same `edition` prop the hero counter
 * uses — so they cannot drift apart the way the prototype's hardcoded 73 and
 * 27 already had.
 */
export function HomePreorder({
    edition,
    includes,
}: {
    edition: Edition;
    includes?: string[];
}) {
    const items = includes && includes.length > 0 ? includes : DEFAULT_INCLUDES;

    const { t } = useTranslation();
    const { openPurchase, openCertificate, openNewsletter } = useShellActions();
    const { priceLabel, deliveryLabel } = useCheckoutDisplay();
    const purchasesPaused = usePurchasesPaused();
    const section = useRef<HTMLDivElement>(null);
    const [shown, setShown] = useState(0);
    const [fill, setFill] = useState(0);
    const started = useRef(false);

    useEffect(() => {
        const node = section.current;

        if (!node) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (
                    !entries.some((entry) => entry.isIntersecting) ||
                    started.current
                ) {
                    return;
                }

                started.current = true;
                setFill(
                    Math.min(
                        100,
                        Math.round((edition.reserved / edition.total) * 100),
                    ),
                );

                const start = performance.now();
                const duration = 1400;

                const step = (time: number) => {
                    const progress = Math.min(1, (time - start) / duration);

                    setShown(Math.round(edition.reserved * progress));

                    if (progress < 1) {
                        requestAnimationFrame(step);
                    }
                };

                requestAnimationFrame(step);
            },
            { threshold: 0.3 },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [edition.reserved, edition.total]);

    return (
        <div
            ref={section}
            className="border-y border-gold/10 bg-choc2 py-20 text-cream"
        >
            <Wrap>
                <div className="grid items-center gap-10 ma-lg:grid-cols-2 ma-lg:gap-20">
                    <Reveal>
                        <Eyebrow>{t('Pre-Order Now Open')}</Eyebrow>
                        <h2 className="mt-3 mb-4 font-serif text-[clamp(28px,3.5vw,44px)] leading-[1.1] font-medium">
                            {t('Reserve')}
                            <br />
                            {t('your')}{' '}
                            <em className="text-gold italic">{t('number')}</em>
                        </h2>
                        <p className="mb-6 text-[15px] leading-[1.85] text-sand">
                            {t(
                                'Heritage Nº 001 is available via pre-order. You pay in full upfront and receive your unique edition number. The Founding Edition is produced in a single limited production run of 100 pieces. Orders are delivered after final quality control and production approval.',
                            )}
                        </p>

                        <div className="mb-8 flex flex-col gap-2.5">
                            <div className="flex items-baseline justify-between">
                                <span className="font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                                    {t('Founding Edition Progress')}
                                </span>
                                <span className="font-serif text-[22px] text-gold lining-nums">
                                    {shown} / {edition.total}
                                </span>
                            </div>
                            <div
                                className="h-0.75 bg-gold/15"
                                role="progressbar"
                                aria-valuenow={edition.reserved}
                                aria-valuemin={0}
                                aria-valuemax={edition.total}
                                aria-label={t('Founding Edition Progress')}
                            >
                                <div
                                    className="h-full bg-gold transition-[width] duration-[1600ms] ease-[cubic-bezier(0.2,0.7,0.2,1)]"
                                    style={{ width: `${fill}%` }}
                                />
                            </div>
                            <p className="font-sans text-[9px] tracking-[0.16em] text-stone/80 uppercase">
                                {t(
                                    'Manually updated · live progress active at launch',
                                )}
                            </p>
                        </div>

                        {deliveryLabel ? (
                            <p className="font-sans text-[13px] tracking-[0.1em] text-stone">
                                {deliveryLabel}
                            </p>
                        ) : null}
                    </Reveal>

                    <Reveal className="border border-gold/20 bg-white/3 p-10">
                        <div className="mb-1 font-serif text-[48px] leading-none font-light text-cream lining-nums">
                            {priceLabel}
                        </div>
                        <div className="mb-7 font-sans text-[9px] tracking-[0.2em] text-stone uppercase">
                            {t('Full upfront · Including Heritage Experience')}
                        </div>
                        <ul className="mb-7 flex flex-col gap-2.5">
                            {items.map((item) => (
                                <li
                                    key={item}
                                    className="flex items-center gap-2.5 font-sans text-[14px] text-sand before:font-sans before:text-[13px] before:text-gold before:content-['→']"
                                >
                                    {t(item)}
                                </li>
                            ))}
                        </ul>
                        <button
                            type="button"
                            onClick={openCertificate}
                            className="mb-4 block font-sans text-[9px] tracking-[0.2em] text-gold uppercase underline-offset-4 hover:underline"
                        >
                            {t('View certificate sample →')}
                        </button>
                        {edition.soldOut ? (
                            <MaisonButton
                                variant="gold"
                                block
                                onClick={openNewsletter}
                                className="mb-3"
                            >
                                {t('Sign up for the Heritage Letter')}
                            </MaisonButton>
                        ) : (
                            <>
                                <MaisonButton
                                    variant="gold"
                                    block
                                    disabled={purchasesPaused}
                                    onClick={() => openPurchase()}
                                    className="mb-3"
                                >
                                    {t('Reserve Your Number')}
                                </MaisonButton>
                                {purchasesPaused ? (
                                    <p className="mb-3 text-center font-sans text-[10px] tracking-[0.12em] text-cream/70">
                                        {t(
                                            'Reservations are temporarily paused.',
                                        )}
                                    </p>
                                ) : null}
                                <MaisonButton
                                    variant="outlineCream"
                                    block
                                    onClick={openNewsletter}
                                >
                                    {t('Sign up for the Heritage Letter')}
                                </MaisonButton>
                            </>
                        )}
                    </Reveal>
                </div>
            </Wrap>
        </div>
    );
}
