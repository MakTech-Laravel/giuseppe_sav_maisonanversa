import { router } from '@inertiajs/react';
import { createContext, useContext, useRef } from 'react';
import type { ReactNode } from 'react';
import { gsap, TRANSITION_EASE, useGSAP } from '@/lib/gsap';

/*
 * The prototype's timings: the overlay reaches full opacity over 450ms, the page
 * swaps at 400ms, and after a 50ms hold the overlay clears. Below 560px the wash
 * runs at 300ms, so the swap moves in proportion with it.
 */
const COVER = 0.45;
const COVER_MOBILE = 0.3;
const SWAP_RATIO = 0.4 / 0.45;
const HOLD = 0.05;

type Navigate = (href: string) => void;

const PageTransitionContext = createContext<Navigate>((href) =>
    router.visit(href),
);

/**
 * Navigates with the chocolate wash drawn over the page swap.
 *
 * Replaces the prototype's monkey-patched global `showPage`. Falls through to an
 * ordinary visit under reduced motion, and for anything the site does not
 * initiate — redirects, back and forward — where the browser owns the timing.
 */
export function usePageTransition(): Navigate {
    return useContext(PageTransitionContext);
}

export function PageTransition({ children }: { children: ReactNode }) {
    const overlay = useRef<HTMLDivElement>(null);
    /** Resolved by `matchMedia`, so it tracks the visitor's current conditions. */
    const config = useRef({ animated: false, cover: COVER });

    useGSAP(() => {
        const media = gsap.matchMedia();

        media.add(
            {
                motion: '(prefers-reduced-motion: no-preference)',
                mobile: '(max-width: 560px)',
            },
            (context) => {
                const { motion, mobile } = context.conditions as {
                    motion: boolean;
                    mobile: boolean;
                };

                config.current = {
                    animated: motion,
                    cover: mobile ? COVER_MOBILE : COVER,
                };

                return () => {
                    config.current = { animated: false, cover: COVER };
                };
            },
        );

        /*
         * Clearing the wash is driven by Inertia rather than by the timeline, so
         * it lifts when the new page has actually rendered instead of at a time
         * guessed in advance.
         */
        const off = router.on('finish', () => {
            const element = overlay.current;

            if (!element || gsap.getProperty(element, 'opacity') === 0) {
                return;
            }

            gsap.to(element, {
                opacity: 0,
                duration: config.current.cover,
                ease: TRANSITION_EASE,
                delay: HOLD,
                onComplete: () => gsap.set(element, { pointerEvents: 'none' }),
            });
        });

        return () => {
            off();
            media.revert();
        };
    });

    const navigate = (href: string) => {
        const element = overlay.current;
        const { animated, cover } = config.current;

        if (!element || !animated) {
            router.visit(href);

            return;
        }

        gsap.timeline()
            .set(element, { pointerEvents: 'auto' })
            .to(element, { opacity: 1, duration: cover, ease: TRANSITION_EASE })
            // Swapping just before the wash is fully opaque hides the reflow.
            .call(() => router.visit(href), undefined, cover * SWAP_RATIO);
    };

    return (
        <PageTransitionContext.Provider value={navigate}>
            {children}
            <div ref={overlay} className="cine-transition" aria-hidden="true" />
        </PageTransitionContext.Provider>
    );
}
