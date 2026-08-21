import { usePage } from '@inertiajs/react';
import { imageAsset, imageVariants, srcset } from '@/lib/imagery';
import type { ImageAssetName } from '@/lib/imagery';
import { cn } from '@/lib/utils';

type PlaceholderImageProps = {
    asset: ImageAssetName;
    /**
     * Overrides the asset's natural shape. Pass `null` to fill the parent,
     * which is what a container that already sets its own ratio wants.
     */
    ratio?: string | null;
    /** Defaults to the asset's caption; pass `''` for decorative imagery. */
    alt?: string;
    /** Hides the caption while keeping the gradient, for backdrops. */
    captioned?: boolean;
    /** Layered over the image, matching the prototype's scrims. */
    overlay?: string;
    className?: string;
    loading?: 'eager' | 'lazy';
    fetchPriority?: 'high' | 'low' | 'auto';
    sizes?: string;
};

/**
 * Renders a site photograph, falling back to a brand-palette placeholder while
 * the real file is missing. The server reports which files exist, so no request
 * is made for absent photography and adding it later needs no code change.
 */
export function PlaceholderImage({
    asset,
    ratio,
    alt,
    captioned = true,
    overlay,
    className,
    loading = 'lazy',
    fetchPriority,
    sizes = '(max-width: 768px) 768px, (max-width: 1280px) 1280px, 2560px',
}: PlaceholderImageProps) {
    const { availableImages } = usePage().props;
    const {
        path,
        ratio: naturalRatio,
        label,
        gradient,
        ground,
    } = imageAsset(asset);

    const exists = availableImages.includes(path);
    const aspectRatio = ratio === null ? undefined : (ratio ?? naturalRatio);
    const available = new Set(availableImages);
    const variants = imageVariants(path).filter(
        (variant) => available.has(variant.webp) || available.has(variant.avif),
    );
    const avifSrcset = srcset(
        variants.filter((variant) => available.has(variant.avif)),
        'avif',
    );
    const webpSrcset = srcset(
        variants.filter((variant) => available.has(variant.webp)),
        'webp',
    );

    return (
        <div
            className={cn('relative overflow-hidden', className)}
            style={{ aspectRatio }}
        >
            {exists ? (
                <picture>
                    {avifSrcset !== '' && (
                        <source
                            type="image/avif"
                            srcSet={avifSrcset}
                            sizes={sizes}
                        />
                    )}
                    {webpSrcset !== '' && (
                        <source
                            type="image/webp"
                            srcSet={webpSrcset}
                            sizes={sizes}
                        />
                    )}
                    <img
                        src={`/${path}`}
                        alt={alt ?? label}
                        loading={loading}
                        fetchPriority={fetchPriority}
                        className="h-full w-full object-cover"
                    />
                </picture>
            ) : (
                <div
                    role={alt === '' ? 'presentation' : 'img'}
                    aria-label={alt === '' ? undefined : (alt ?? label)}
                    className="flex h-full w-full items-center justify-center"
                    style={{ backgroundImage: gradient }}
                >
                    {captioned && (
                        <span
                            className={cn(
                                'px-4 text-center font-sans text-[9px] font-light tracking-[0.35em] uppercase',
                                ground === 'light'
                                    ? 'text-choc/45'
                                    : 'text-cream/35',
                            )}
                        >
                            {label}
                        </span>
                    )}
                </div>
            )}

            {overlay && (
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0"
                    style={{ backgroundImage: overlay }}
                />
            )}
        </div>
    );
}
