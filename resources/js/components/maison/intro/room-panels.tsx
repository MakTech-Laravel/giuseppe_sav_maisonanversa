import { useRef } from 'react';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { useParallax } from '@/hooks/use-parallax';
import { gsap, useGSAP } from '@/lib/gsap';
import { INTRO_ROOMS } from '@/lib/maison-intro';

/** The prototype's `transition: opacity 1.4s`. */
const CROSSFADE = 1.4;

/** Its `transform 8s ease-out` from `scale(1.08)`. */
const ZOOM = { from: 1.08, duration: 8 } as const;

/**
 * The six photographs behind the intro, crossfading with a slow zoom.
 *
 * No `matchMedia` guard here, unlike the rest of the cinematic layer: the intro
 * as a whole does not exist under reduced motion, so by the time these mount the
 * question has already been answered.
 *
 * @param panel Which room is in front, or `-1` for none, which is how the stage
 *   holds the sequence back while the loader is still up. Two slides share the
 *   courtyard, so this is the panel index rather than the slide index — passing
 *   the slide would restart the zoom when the closing card arrives over a
 *   photograph that never moved.
 */
export function RoomPanels({ panel }: { panel: number }) {
    const drift = useRef<HTMLDivElement>(null);
    const panels = useRef<(HTMLDivElement | null)[]>([]);

    useParallax(drift);

    useGSAP(
        () => {
            panels.current.forEach((element, index) => {
                if (!element) {
                    return;
                }

                const active = index === panel;

                gsap.to(element, {
                    opacity: active ? 1 : 0,
                    duration: CROSSFADE,
                    ease: 'power1.inOut',
                    overwrite: 'auto',
                });

                /*
                 * Only the incoming panel is (re)zoomed. The outgoing one keeps
                 * whatever scale it had reached and simply fades, so the two
                 * frames never disagree about where the room is.
                 */
                if (active) {
                    gsap.fromTo(
                        element,
                        { scale: ZOOM.from },
                        {
                            scale: 1,
                            duration: ZOOM.duration,
                            ease: 'power1.out',
                            overwrite: 'auto',
                        },
                    );
                }
            });
        },
        { dependencies: [panel] },
    );

    return (
        <div
            ref={drift}
            aria-hidden="true"
            /* Overshoots the viewport so the parallax drift never bares an edge. */
            className="absolute -inset-5"
        >
            {INTRO_ROOMS.map((room, index) => (
                <div
                    key={room}
                    ref={(element) => {
                        panels.current[index] = element;
                    }}
                    className="absolute inset-0 opacity-0"
                >
                    <PlaceholderImage
                        asset={room}
                        ratio={null}
                        alt=""
                        captioned={false}
                        loading="eager"
                        className="h-full w-full [&_img]:h-full [&_img]:w-full [&_img]:object-cover [&_img]:object-center"
                    />
                </div>
            ))}
        </div>
    );
}
