import { PlaceholderImage } from '@/components/maison/placeholder-image';
import type { ImageAssetName } from '@/lib/imagery';
import { cn } from '@/lib/utils';

type StoryMediaFrameProps = {
    /** When set, renders the brand/product asset (caption-free). */
    asset?: ImageAssetName;
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
    ratio = '4 / 5',
    className,
    contain = false,
    loading = 'lazy',
}: StoryMediaFrameProps) {
    if (asset) {
        return (
            <PlaceholderImage
                asset={asset}
                alt=""
                captioned={false}
                ratio={ratio}
                loading={loading}
                className={cn(
                    contain && '[&_img]:object-contain [&_img]:p-6',
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
                aspectRatio: ratio === null ? undefined : ratio,
                backgroundImage:
                    'linear-gradient(165deg, #3a2a22 0%, #2b1d18 55%, #8d705a 140%)',
            }}
        />
    );
}
