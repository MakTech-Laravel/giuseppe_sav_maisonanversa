import { usePage } from '@inertiajs/react';
import type { RefObject } from 'react';
import { gsap, MAISON_EASE, MEDIA, ScrollTrigger, useGSAP } from '@/lib/gsap';

export const REVEAL_VARIANTS = [
    'up',
    'fade',
    'scale',
    'left',
    'right',
] as const;

export type RevealVariant = (typeof REVEAL_VARIANTS)[number];

/** Where each variant starts, given the distance it travels. */
function from(variant: RevealVariant, travel: number): gsap.TweenVars {
    switch (variant) {
        case 'fade':
            return { opacity: 0 };
        case 'scale':
            return { opacity: 0, scale: 0.95 };
        case 'left':
            return { opacity: 0, x: -travel };
        case 'right':
            return { opacity: 0, x: travel };
        default:
            return { opacity: 0, y: travel };
    }
}

/** Where it ends, in every case: exactly where the layout put it. */
const TO: gsap.TweenVars = { opacity: 1, x: 0, y: 0, scale: 1 };

/**
 * Reveals `[data-reveal]` elements as they scroll into view.
 *
 * Three things differ from the prototype, all of them fixes rather than
 * choices. It built a fresh `IntersectionObserver` on every navigation and
 * never disconnected the old ones, so observers accumulated for the whole
 * session; `ScrollTrigger` here is created inside a `useGSAP` scope and torn
 * down with it. Its stagger came from five hardcoded delay classes, capping a
 * group at five members; `ScrollTrigger.batch` staggers whatever arrives in the
 * same frame. And `start: 'top 92%'` is the threshold-0.08 equivalent.
 *
 * @param scope The subtree to search. Reveals rebind when the page changes.
 */
export function useReveal(scope: RefObject<HTMLElement | null>) {
    const page = usePage();

    useGSAP(
        () => {
            const media = gsap.matchMedia();

            /* The prototype travels 40px over 1.1s, and 25px over 0.8s below
             * 560px. Declaring both as contexts means a resize across the
             * breakpoint reverts one and builds the other. */
            const register = (travel: number, duration: number) => () => {
                const elements = gsap.utils.toArray<HTMLElement>(
                    '[data-reveal]',
                    scope.current,
                );

                if (elements.length === 0) {
                    return;
                }

                for (const variant of REVEAL_VARIANTS) {
                    const group = elements.filter(
                        (element) =>
                            (element.dataset.reveal || 'up') === variant,
                    );

                    if (group.length === 0) {
                        continue;
                    }

                    gsap.set(group, from(variant, travel));

                    ScrollTrigger.batch(group, {
                        start: 'top 92%',
                        once: true,
                        onEnter: (batch) =>
                            gsap.to(batch, {
                                ...TO,
                                duration,
                                ease: MAISON_EASE,
                                stagger: 0.1,
                                overwrite: true,
                            }),
                    });
                }
            };

            media.add(MEDIA.motionDesktop, register(40, 1.1));
            media.add(MEDIA.motionMobile, register(25, 0.8));

            return () => media.revert();
        },
        // Rebinds after an Inertia page swap, reverting the previous page's set.
        { dependencies: [page.url], revertOnUpdate: true },
    );
}
