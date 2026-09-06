import { useEffect, useMemo, useRef, useState } from 'react';
import type {
    CSSProperties,
    MouseEvent,
    PointerEvent as ReactPointerEvent,
} from 'react';
import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import type { ImageAssetName } from '@/lib/imagery';
import { IMAGE_ASSETS } from '@/lib/imagery';
import { cn } from '@/lib/utils';

const DEFAULT_GALLERY: readonly ImageAssetName[] = [
    'heritage-001-front',
    'heritage-001-detail-gravure',
    'atelier-workshop',
    'heritage-001-lifestyle-court',
] as const;

function isAssetKey(value: string): value is ImageAssetName {
    return value in IMAGE_ASSETS;
}

function isMediaUrl(value: string): boolean {
    return (
        value.startsWith('http://') ||
        value.startsWith('https://') ||
        value.startsWith('/')
    );
}

function ProductMedia({
    src,
    alt,
    className,
    loading = 'lazy',
    fetchPriority,
    overlay,
}: {
    src: string;
    alt: string;
    className?: string;
    loading?: 'eager' | 'lazy';
    fetchPriority?: 'high' | 'low' | 'auto';
    overlay?: string;
}) {
    if (isAssetKey(src)) {
        return (
            <PlaceholderImage
                asset={src}
                ratio={null}
                alt={alt}
                captioned={false}
                loading={loading}
                fetchPriority={fetchPriority}
                overlay={overlay}
                className={className}
            />
        );
    }

    if (!isMediaUrl(src)) {
        return (
            <PlaceholderImage
                asset="heritage-001-front"
                ratio={null}
                alt={alt}
                captioned={false}
                loading={loading}
                fetchPriority={fetchPriority}
                overlay={overlay}
                className={className}
            />
        );
    }

    return (
        <div className={cn('relative overflow-hidden', className)}>
            <img
                src={src}
                alt={alt}
                loading={loading}
                fetchPriority={fetchPriority}
                className="absolute inset-0 h-full w-full object-cover"
            />
            {overlay ? (
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0"
                    style={{ background: overlay }}
                />
            ) : null}
        </div>
    );
}

/**
 * Keep the active thumbnail visible inside the strip without scrolling the page.
 */
function scrollThumbIntoStrip(
    strip: HTMLDivElement,
    thumb: HTMLButtonElement,
    behavior: ScrollBehavior,
): void {
    const stripRect = strip.getBoundingClientRect();
    const thumbRect = thumb.getBoundingClientRect();
    const offset =
        thumbRect.left < stripRect.left
            ? thumbRect.left - stripRect.left
            : thumbRect.right > stripRect.right
              ? thumbRect.right - stripRect.right
              : 0;

    if (offset !== 0) {
        strip.scrollBy({ left: offset, behavior });
    }
}

/**
 * Sticky product gallery with thumbnail switching and cursor-follow zoom.
 *
 * Zoom mirrors the prototype (`background-size: 220%` under the pointer) but
 * scales the image node instead, so both real photographs and brand-palette
 * placeholders respond the same way. Fine pointers only; reduced motion skips it.
 */
export function ProductGallery({
    gallery = [],
    productName,
}: {
    gallery?: string[];
    productName?: string;
}) {
    const { t } = useTranslation();
    const resolvedProductName = productName ?? t('Heritage No.001');
    const slides = useMemo(
        () => (gallery.length > 0 ? gallery : [...DEFAULT_GALLERY]),
        [gallery],
    );
    const [active, setActive] = useState(0);
    const [zoomEnabled, setZoomEnabled] = useState(false);
    const [zooming, setZooming] = useState(false);
    const [origin, setOrigin] = useState({ x: 50, y: 50 });
    const stripRef = useRef<HTMLDivElement>(null);
    const thumbRefs = useRef<Array<HTMLButtonElement | null>>([]);
    const dragState = useRef({
        active: false,
        moved: false,
        dragging: false,
        startX: 0,
        scrollLeft: 0,
        pointerId: -1,
        thumbIndex: -1,
    });
    const hasOverflow = slides.length > 4;

    const editionMark = useMemo(() => {
        const match = resolvedProductName.match(/(\d{3})/);

        return match?.[1] ?? '001';
    }, [resolvedProductName]);

    useEffect(() => {
        const media = window.matchMedia(
            '(prefers-reduced-motion: no-preference) and (pointer: fine)',
        );

        const sync = () => setZoomEnabled(media.matches);

        sync();
        media.addEventListener('change', sync);

        return () => media.removeEventListener('change', sync);
    }, []);

    useEffect(() => {
        const strip = stripRef.current;
        const thumb = thumbRefs.current[active];

        if (!strip || !thumb) {
            return;
        }

        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        scrollThumbIntoStrip(
            strip,
            thumb,
            prefersReducedMotion ? 'auto' : 'smooth',
        );
    }, [active, slides.length]);

    useEffect(() => {
        const strip = stripRef.current;

        if (!strip) {
            return;
        }

        const onWheel = (event: WheelEvent) => {
            if (strip.scrollWidth <= strip.clientWidth) {
                return;
            }

            const delta =
                Math.abs(event.deltaX) > Math.abs(event.deltaY)
                    ? event.deltaX
                    : event.deltaY;

            if (delta === 0) {
                return;
            }

            event.preventDefault();
            strip.scrollLeft += delta;
        };

        strip.addEventListener('wheel', onWheel, { passive: false });

        return () => {
            strip.removeEventListener('wheel', onWheel);
        };
    }, [slides.length]);

    function handleStripPointerDown(event: ReactPointerEvent<HTMLDivElement>) {
        if (event.button !== 0) {
            return;
        }

        const strip = stripRef.current;

        if (!strip) {
            return;
        }

        const thumb = (event.target as HTMLElement | null)?.closest(
            '[role="tab"]',
        );
        const thumbIndex = thumb
            ? thumbRefs.current.findIndex((element) => element === thumb)
            : -1;

        dragState.current = {
            active: true,
            moved: false,
            dragging: false,
            startX: event.clientX,
            scrollLeft: strip.scrollLeft,
            pointerId: event.pointerId,
            thumbIndex,
        };
    }

    function handleStripPointerMove(event: ReactPointerEvent<HTMLDivElement>) {
        const drag = dragState.current;
        const strip = stripRef.current;

        if (!drag.active || !strip || event.pointerId !== drag.pointerId) {
            return;
        }

        const delta = event.clientX - drag.startX;

        if (Math.abs(delta) <= 8) {
            return;
        }

        if (!drag.dragging) {
            drag.dragging = true;
            drag.moved = true;
            strip.setPointerCapture(event.pointerId);
        }

        strip.scrollLeft = drag.scrollLeft - delta;
    }

    function finishStripDrag(event: ReactPointerEvent<HTMLDivElement>) {
        const drag = dragState.current;
        const strip = stripRef.current;

        if (!drag.active || !strip || event.pointerId !== drag.pointerId) {
            return;
        }

        if (!drag.moved && drag.thumbIndex >= 0) {
            setActive(drag.thumbIndex);
        }

        drag.active = false;
        drag.dragging = false;
        drag.moved = false;

        if (strip.hasPointerCapture(event.pointerId)) {
            strip.releasePointerCapture(event.pointerId);
        }
    }

    function handleMove(event: MouseEvent<HTMLDivElement>) {
        if (!zoomEnabled) {
            return;
        }

        const rect = event.currentTarget.getBoundingClientRect();
        setOrigin({
            x: ((event.clientX - rect.left) / rect.width) * 100,
            y: ((event.clientY - rect.top) / rect.height) * 100,
        });
        setZooming(true);
    }

    const zoomStyle: CSSProperties = {
        transform: zooming ? 'scale(2.2)' : 'scale(1)',
        transformOrigin: `${origin.x}% ${origin.y}%`,
        transition: zooming ? 'none' : 'transform 0.2s ease',
    };

    return (
        <div className="min-w-0 lg:sticky lg:top-[calc(var(--topbar-h)+var(--nav-h)+20px)]">
            <div
                className={cn(
                    'relative mb-3 aspect-4/5 overflow-hidden bg-choc2',
                    zoomEnabled && 'cursor-zoom-in',
                )}
                onMouseMove={handleMove}
                onMouseLeave={() => setZooming(false)}
            >
                <div className="absolute inset-0" style={zoomStyle}>
                    <ProductMedia
                        src={slides[active] ?? slides[0]}
                        alt={resolvedProductName}
                        loading="eager"
                        fetchPriority="high"
                        overlay="linear-gradient(to top, rgba(41,28,24,0.5) 0%, rgba(41,28,24,0.05) 45%)"
                        className="h-full w-full"
                    />
                </div>
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute right-7 bottom-7 font-serif text-[64px] leading-none font-light text-gold/10"
                >
                    {editionMark}
                </div>
            </div>

            <div
                ref={stripRef}
                className={cn(
                    'flex w-full min-w-0 touch-pan-x scrollbar-none gap-2 overflow-x-auto overscroll-x-contain scroll-smooth [-ms-overflow-style:none]',
                    hasOverflow && 'cursor-grab active:cursor-grabbing',
                )}
                role="tablist"
                aria-label={resolvedProductName}
                onPointerDown={handleStripPointerDown}
                onPointerMove={handleStripPointerMove}
                onPointerUp={finishStripDrag}
                onPointerCancel={finishStripDrag}
            >
                {slides.map((asset, index) => (
                    <button
                        key={`${asset}-${index}`}
                        ref={(element) => {
                            thumbRefs.current[index] = element;
                        }}
                        type="button"
                        role="tab"
                        aria-selected={index === active}
                        aria-label={`${resolvedProductName} ${index + 1}`}
                        onClick={() => setActive(index)}
                        className={cn(
                            'aspect-square shrink-0 basis-[calc((100%-1.5rem)/4)] overflow-hidden border transition-colors select-none',
                            index === active
                                ? 'border-gold'
                                : 'border-gold/10 hover:border-gold',
                        )}
                    >
                        <ProductMedia
                            src={asset}
                            alt=""
                            className="pointer-events-none h-full w-full"
                        />
                    </button>
                ))}
            </div>
        </div>
    );
}
