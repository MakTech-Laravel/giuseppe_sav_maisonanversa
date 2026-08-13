import { useRef, useSyncExternalStore } from 'react';
import { createPortal } from 'react-dom';
import { gsap, MEDIA, useGSAP } from '@/lib/gsap';

/** Anything that should swell the ring when the pointer is over it. */
const GROW_TARGETS =
    'a, button, [data-magnetic], [data-cursor-grow], summary, [role="button"]';

/**
 * The 28px ring trailing the pointer, plus the 4px dot pinned to it exactly.
 *
 * Portalled to the body: `mix-blend-mode: difference` stops reading through to
 * the page as soon as an ancestor forms a stacking context, and any wrapper
 * GSAP has transformed does exactly that.
 *
 * Hover detection is delegated from the document rather than bound per element,
 * so content rendered later — feed posts, the hundred-cell number grid — grows
 * the ring without anything having to rebind.
 *
 * Portal mounts only after hydration: SSR and the first client paint both
 * render `null`, so React does not try to hydrate the cursor against the next
 * sibling in the shell (which caused a mismatch with the topbar).
 */
function subscribeClientOnly(callback: () => void): () => void {
    void callback;

    return () => {};
}

function getClientOnlySnapshot(): boolean {
    return true;
}

function getServerOnlySnapshot(): boolean {
    return false;
}

export function CustomCursor() {
    const mounted = useSyncExternalStore(
        subscribeClientOnly,
        getClientOnlySnapshot,
        getServerOnlySnapshot,
    );
    const ring = useRef<HTMLDivElement>(null);
    const dot = useRef<HTMLDivElement>(null);

    useGSAP(
        () => {
            if (!mounted) {
                return;
            }

            const media = gsap.matchMedia();

            media.add(MEDIA.pointer, () => {
                const ringEl = ring.current;
                const dotEl = dot.current;

                if (!ringEl || !dotEl) {
                    return;
                }

                // Centred on the pointer, and stays centred as the ring grows.
                gsap.set([ringEl, dotEl], { xPercent: -50, yPercent: -50 });

                /*
                 * `quickTo` writes off the shared ticker instead of a loop of its
                 * own. The 0.4s power3 settle reproduces the prototype's 0.12
                 * per-frame lerp, whose time constant is about 130ms.
                 */
                const ringX = gsap.quickTo(ringEl, 'x', {
                    duration: 0.4,
                    ease: 'power3',
                });
                const ringY = gsap.quickTo(ringEl, 'y', {
                    duration: 0.4,
                    ease: 'power3',
                });

                let visible = false;

                const onMove = (event: PointerEvent) => {
                    ringX(event.clientX);
                    ringY(event.clientY);

                    // The dot is unsmoothed, so it sits exactly under the pointer.
                    gsap.set(dotEl, { x: event.clientX, y: event.clientY });

                    if (!visible) {
                        visible = true;
                        gsap.to([ringEl, dotEl], { opacity: 1, duration: 0.3 });
                    }
                };

                const onLeave = () => {
                    visible = false;
                    gsap.to([ringEl, dotEl], { opacity: 0, duration: 0.3 });
                };

                const onOver = (event: PointerEvent) => {
                    const target = event.target as Element | null;

                    ringEl.dataset.grow = String(
                        Boolean(target?.closest?.(GROW_TARGETS)),
                    );
                };

                document.addEventListener('pointermove', onMove);
                document.addEventListener('pointerover', onOver);
                document.documentElement.addEventListener(
                    'pointerleave',
                    onLeave,
                );

                return () => {
                    document.removeEventListener('pointermove', onMove);
                    document.removeEventListener('pointerover', onOver);
                    document.documentElement.removeEventListener(
                        'pointerleave',
                        onLeave,
                    );
                };
            });

            return () => media.revert();
        },
        { dependencies: [mounted] },
    );

    if (!mounted) {
        return null;
    }

    return createPortal(
        <>
            <div ref={ring} className="cine-cursor" aria-hidden="true" />
            <div ref={dot} className="cine-cursor-dot" aria-hidden="true" />
        </>,
        document.body,
    );
}
