import { gsap, MAISON_EASE, MEDIA, useGSAP } from '@/lib/gsap';

type QuickTo = ReturnType<typeof gsap.quickTo>;

/** The prototype's offset-from-centre multiplier. */
const PULL = 0.12;

/**
 * Buttons that lean towards the pointer.
 *
 * The prototype bound a listener per button at parse time, so anything rendered
 * afterwards was inert. This delegates from the document instead and creates
 * the tweens on first contact, which means a `data-magnetic` element added at
 * any point behaves the same as one present from the start.
 *
 * Displacement is intentionally left unclamped, as in the prototype: the pull is
 * a fraction of the distance from the centre, so it is already bounded by the
 * element's own half-size — about 11px on the hero buttons.
 */
export function useMagnetic() {
    useGSAP(() => {
        const media = gsap.matchMedia();

        media.add(MEDIA.pointer, () => {
            const setters = new WeakMap<Element, { x: QuickTo; y: QuickTo }>();

            const quickTo = (element: Element) => {
                let setter = setters.get(element);

                if (!setter) {
                    const options = { duration: 0.4, ease: MAISON_EASE };

                    setter = {
                        x: gsap.quickTo(element, 'x', options),
                        y: gsap.quickTo(element, 'y', options),
                    };

                    setters.set(element, setter);
                }

                return setter;
            };

            const onMove = (event: PointerEvent) => {
                const element = (event.target as Element | null)?.closest?.(
                    '[data-magnetic]',
                );

                if (!element) {
                    return;
                }

                const bounds = element.getBoundingClientRect();
                const setter = quickTo(element);

                setter.x(
                    (event.clientX - bounds.left - bounds.width / 2) * PULL,
                );
                setter.y(
                    (event.clientY - bounds.top - bounds.height / 2) * PULL,
                );
            };

            const onOut = (event: PointerEvent) => {
                const element = (event.target as Element | null)?.closest?.(
                    '[data-magnetic]',
                );

                if (!element || element.contains(event.relatedTarget as Node)) {
                    return;
                }

                const setter = quickTo(element);

                setter.x(0);
                setter.y(0);
            };

            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerout', onOut);

            return () => {
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerout', onOut);
            };
        });

        return () => media.revert();
    });
}
