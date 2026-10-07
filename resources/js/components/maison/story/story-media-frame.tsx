import { PlaceholderImage } from '@/components/maison/placeholder-image';
import type { ImageAssetName } from '@/lib/imagery';
import { cn } from '@/lib/utils';

type StoryMediaFrameProps = {
    /** When set, renders the brand/product asset (caption-free). */
    asset?: ImageAssetName;
    /**
     * Overrides the asset's natural shape. Omit to use the asset ratio (or
     * `4 / 5` for empty placeholder slots). Pass `null` to fill the parent.
     */
    ratio?: string | null;
    className?: string;
    /** Object-fit for real images; illustrations often need contain. */
    contain?: boolean;
    loading?: 'eager' | 'lazy';
};

/**
 * Editorial media slot for Ons Verhaal. Never prints [FOTO] placeholder copy —
 * missing photography falls back to a silent brand gradient.
 */
export function StoryMediaFrame({
    asset,
    ratio,
    className,
    contain = false,
    loading = 'lazy',
}: StoryMediaFrameProps) {
    const resolvedRatio =
        ratio !== undefined ? ratio : asset ? undefined : '4 / 5';

    if (asset) {
        return (
            <PlaceholderImage
                asset={asset}
                alt=""
                captioned={false}
                ratio={resolvedRatio}
                loading={loading}
                className={cn(
                    contain && '[&_img]:object-contain [&_img]:p-0',
                    className,
                )}
            />
        );
    }

    return (
        <div
            aria-hidden="true"
            className={cn('overflow-hidden bg-choc2', className)}
            style={{
                aspectRatio:
                    resolvedRatio === null || resolvedRatio === undefined
                        ? undefined
                        : resolvedRatio,
                backgroundImage:
                    'linear-gradient(165deg, #3a2a22 0%, #2b1d18 55%, #8d705a 140%)',
            }}
        />
    );
}
