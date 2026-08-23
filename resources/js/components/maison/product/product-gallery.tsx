import { useEffect, useMemo, useState } from 'react';
import type { CSSProperties, MouseEvent } from 'react';
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
 * Sticky product gallery with thumbnail switching and cursor-follow zoom.
 *
 * Zoom mirrors the prototype (`background-size: 220%` under the pointer) but
 * scales the image node instead, so both real photographs and brand-palette
 * placeholders respond the same way. Fine pointers only; reduced motion skips it.
 */
export function ProductGallery({ gallery = [] }: { gallery?: string[] }) {
    const slides = useMemo(
        () => (gallery.length > 0 ? gallery : [...DEFAULT_GALLERY]),
        [gallery],
    );
    const [active, setActive] = useState(0);
    const [zoomEnabled, setZoomEnabled] = useState(false);
    const [zooming, setZooming] = useState(false);
    const [origin, setOrigin] = useState({ x: 50, y: 50 });

    useEffect(() => {
        const media = window.matchMedia(
            '(prefers-reduced-motion: no-preference) and (pointer: fine)',
        );

        const sync = () => setZoomEnabled(media.matches);

        sync();
        media.addEventListener('change', sync);

        return () => media.removeEventListener('change', sync);
    }, []);

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
        <div className="lg:sticky lg:top-[calc(var(--topbar-h)+var(--nav-h)+20px)]">
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
                        alt="Heritage No.001"
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
                    001
                </div>
            </div>

            <div
                className="grid grid-cols-4 gap-2"
                role="tablist"
                aria-label="Heritage No.001"
            >
                {slides.map((asset, index) => (
                    <button
                        key={`${asset}-${index}`}
                        type="button"
                        role="tab"
                        aria-selected={index === active}
                        aria-label={`Heritage No.001 ${index + 1}`}
                        data-magnetic
                        onClick={() => setActive(index)}
                        className={cn(
                            'aspect-square overflow-hidden border transition-colors',
                            index === active
                                ? 'border-gold'
                                : 'border-gold/10 hover:border-gold',
                        )}
                    >
                        <ProductMedia
                            src={asset}
                            alt=""
                            className="h-full w-full"
                        />
                    </button>
                ))}
            </div>
        </div>
    );
}
