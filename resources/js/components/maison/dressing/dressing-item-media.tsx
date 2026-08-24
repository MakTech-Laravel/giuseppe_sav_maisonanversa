import { PlaceholderImage } from '@/components/maison/placeholder-image';
import type { ImageAssetName } from '@/lib/imagery';
import { IMAGE_ASSETS } from '@/lib/imagery';

function isAssetKey(value: string): value is ImageAssetName {
    return value in IMAGE_ASSETS;
}

/**
 * A dressing item's cover: an uploaded photograph renders directly, while a
 * seeded `image_key` with no photograph on disk yet falls back to the same
 * brand-palette PlaceholderImage every other page uses.
 */
export function DressingItemMedia({
    imageUrl,
    imageKey,
    alt,
    className,
}: {
    imageUrl?: string | null;
    imageKey?: string | null;
    alt: string;
    className?: string;
}) {
    if (imageUrl) {
        return (
            <img
                src={imageUrl}
                alt={alt}
                loading="lazy"
                className={className ?? 'h-full w-full object-cover'}
            />
        );
    }

    const asset = imageKey && isAssetKey(imageKey) ? imageKey : 'room-dressing';

    return (
        <PlaceholderImage
            asset={asset}
            ratio={null}
            alt={alt}
            captioned={false}
            className={className}
        />
    );
}
