import { useLayoutEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { gsap, MAISON_EASE, MEDIA, useGSAP } from '@/lib/gsap';
import { BOOT_COVER_ID } from '@/lib/maison-intro';

type PreloaderProps = {
    /** 0 to 1. The bar tracks real loading rather than a fixed timer. */
    progress: number;
    /** Plays the exit and calls back once the curtain has lifted. */
    done: boolean;
    onFinished: () => void;
};

/**
 * The opening curtain: the wordmark rises from behind a mask, the subtitle and
 * then the progress bar settle in beneath it.
 *
 * The choreography is a single timeline rather than the prototype's nested
 * `setTimeout` chain, so it can be reversed, seeked or killed as one thing —
 * which is what the reduced-motion path and an early exit both need.
 */
export function Preloader({ progress, done, onFinished }: PreloaderProps) {
    const { t } = useTranslation();
    const root = useRef<HTMLDivElement>(null);
    const wordmark = useRef<HTMLSpanElement>(null);
    const subtitle = useRef<HTMLParagraphElement>(null);
    const bar = useRef<HTMLDivElement>(null);
    const fill = useRef<HTMLDivElement>(null);

    /*
     * The blade boot cover is the same chocolate as this curtain. Drop it once
     * this layer is in the tree so the home page never peeks through.
     */
    useLayoutEffect(() => {
        document.getElementById(BOOT_COVER_ID)?.remove();
    }, []);

    useGSAP(() => {
        const media = gsap.matchMedia();

        media.add(MEDIA.motion, () => {
            gsap.timeline()
                .fromTo(
                    wordmark.current,
                    { yPercent: 110, opacity: 0 },
                    {
                        yPercent: 0,
                        opacity: 1,
                        duration: 2,
                        ease: MAISON_EASE,
                    },
                    0,
                )
                .fromTo(
                    subtitle.current,
                    { opacity: 0, y: 8 },
                    { opacity: 0.7, y: 0, duration: 1.5 },
                    1,
                )
                .fromTo(
                    bar.current,
                    { opacity: 0, y: 8 },
                    { opacity: 0.7, y: 0, duration: 1.5 },
                    1.5,
                );
        });

        return () => media.revert();
    });

    useGSAP(
        () => {
            gsap.to(fill.current, {
                scaleX: gsap.utils.clamp(0, 1, progress),
                duration: 0.4,
                ease: 'power2.out',
            });
        },
        { dependencies: [progress] },
    );

    useGSAP(
        () => {
            if (!done) {
                return;
            }

            gsap.to(root.current, {
                opacity: 0,
                scale: 1.02,
                duration: 1,
                ease: 'power2.inOut',
                onComplete: onFinished,
            });
        },
        { dependencies: [done] },
    );

    return (
        <div
            ref={root}
            className="fixed inset-0 z-[9995] flex flex-col items-center justify-center gap-4 bg-choc"
            role="status"
            aria-live="polite"
        >
            {/* The mask the wordmark rises from behind. */}
            <span className="block overflow-hidden">
                <span
                    ref={wordmark}
                    className="block font-serif text-[24px] tracking-[0.16em] text-gold ma-sm:text-[30px]"
                >
                    MAISON ANVERSA
                </span>
            </span>

            <p
                ref={subtitle}
                className="font-sans text-[9px] font-light tracking-[0.32em] text-sand uppercase opacity-0"
            >
                {t('Het huis ontwaakt')}
            </p>

            <div
                ref={bar}
                className="mt-2 h-px w-40 overflow-hidden bg-gold/18 opacity-0"
            >
                <div
                    ref={fill}
                    className="h-full w-full origin-left scale-x-0 bg-gold"
                />
            </div>
        </div>
    );
}
