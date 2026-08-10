import type { RefObject } from 'react';
import { gsap, MEDIA, useGSAP } from '@/lib/gsap';

/*
 * The prototype lerps towards the pointer at 0.06 per frame on a mouse and 0.04
 * on a touch screen. Expressed as a settle time those are roughly 270ms and
 * 410ms, which is where these durations come from.
 */
const MOUSE = { travel: 10, duration: 0.8 } as const;
const TOUCH = { travel: 6, duration: 1.2 } as const;

/**
 * Drifts a subtree a few pixels against the pointer, for depth behind the intro.
 *
 * Applied to the container rather than the visible panel, so switching slides
 * needs no rebinding, and only `x`/`y` are touched — the Ken Burns zoom owns
 * `scale`. In the prototype both wrote the same inline `transform`, so whichever
 * ran last erased the other and the zoom silently stopped on desktop.
 *
 * @param scope The element to drift. Its children should overshoot their frame.
 */
export function useParallax(scope: RefObject<HTMLElement | null>) {
    useGSAP(() => {
        const media = gsap.matchMedia();

        /** Both variants differ only in reach and settle time. */
        const drift = ({
            travel,
            duration,
        }: {
            travel: number;
            duration: number;
        }) => {
            const element = scope.current;

            if (!element) {
                return null;
            }

            const options = { duration, ease: 'power2' };
            const x = gsap.quickTo(element, 'x', options);
            const y = gsap.quickTo(element, 'y', options);

            /** Pointer position as -1..1 from the centre of the viewport. */
            return (clientX: number, clientY: number) => {
                x((clientX / window.innerWidth - 0.5) * 2 * travel);
                y((clientY / window.innerHeight - 0.5) * 2 * travel);
            };
        };

        media.add(MEDIA.pointer, () => {
            const move = drift(MOUSE);

            if (!move) {
                return;
            }

            const onMove = (event: PointerEvent) =>
                move(event.clientX, event.clientY);

            window.addEventListener('pointermove', onMove);

            return () => window.removeEventListener('pointermove', onMove);
        });

        media.add(MEDIA.touch, () => {
            const move = drift(TOUCH);

            if (!move) {
                return;
            }

            const onTouch = (event: TouchEvent) => {
                const touch = event.touches[0];

                if (touch) {
                    move(touch.clientX, touch.clientY);
                }
            };

            // Recentres when the finger lifts, as the prototype does.
            const onEnd = () =>
                move(window.innerWidth / 2, window.innerHeight / 2);

            const passive = { passive: true } as const;

            window.addEventListener('touchstart', onTouch, passive);
            window.addEventListener('touchmove', onTouch, passive);
            window.addEventListener('touchend', onEnd, passive);

            return () => {
                window.removeEventListener('touchstart', onTouch);
                window.removeEventListener('touchmove', onTouch);
                window.removeEventListener('touchend', onEnd);
            };
        });

        return () => media.revert();
    });
}

/**
 * The slow zoom out from `scale(1.06)` that gives a still photograph life.
 *
 * Registered only under a motion preference, so reduced motion leaves the frame
 * still — a gap in the prototype, where the zoom kept running regardless.
 *
 * @param scope The element to zoom.
 * @param dependencies Re-runs the zoom, e.g. when the slide changes.
 */
export function useKenBurns(
    scope: RefObject<HTMLElement | null>,
    dependencies: unknown[] = [],
) {
    useGSAP(
        () => {
            const media = gsap.matchMedia();

            media.add(MEDIA.motion, () => {
                if (!scope.current) {
                    return;
                }

                gsap.fromTo(
                    scope.current,
                    { scale: 1.06 },
                    { scale: 1, duration: 8, ease: 'power1.out' },
                );
            });

            return () => media.revert();
        },
        { dependencies, revertOnUpdate: true },
    );
}
