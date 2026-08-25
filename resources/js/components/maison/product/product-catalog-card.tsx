import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Reveal } from '@/components/maison/ui/reveal';
import { useLocale } from '@/hooks/use-locale';
import type { ImageAssetName } from '@/lib/imagery';
import { IMAGE_ASSETS } from '@/lib/imagery';
import { maisonUrl } from '@/lib/maison-navigation';
import type { ProductCard as ProductCardData } from '@/types/product';

function ProductCover({ src, alt }: { src: string | null; alt: string }) {
    const value = src ?? 'heritage-001-front';

    if (value in IMAGE_ASSETS) {
        return (
            <PlaceholderImage
                asset={value as ImageAssetName}
                ratio={null}
                alt={alt}
                captioned={false}
                objectPosition="center 15%"
                className="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-[1.03]"
            />
        );
    }

    if (
        value.startsWith('http://') ||
        value.startsWith('https://') ||
        value.startsWith('/')
    ) {
        return (
            <img
                src={value}
                alt={alt}
                className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                style={{ objectPosition: 'center 15%' }}
            />
        );
    }

    return (
        <PlaceholderImage
            asset="heritage-001-front"
            ratio={null}
            alt={alt}
            captioned={false}
            objectPosition="center 15%"
            className="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-[1.03]"
        />
    );
}

function statusLabel(t: (key: string) => string, status: string): string {
    switch (status) {
        case 'active':
            return t('Beschikbaar');
        case 'coming_soon':
            return t('Binnenkort');
        default:
            return t('Uitverkocht');
    }
}

export function ProductCatalogCard({ product }: { product: ProductCardData }) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const href = `${maisonUrl('products', locale)}/${product.slug}`;

    return (
        <Reveal>
            <MaisonLink
                href={href}
                className="group block h-full border border-gold/10 bg-white/3 transition-colors hover:border-gold/25 hover:bg-gold/5"
            >
                <div className="relative aspect-square overflow-hidden bg-choc2">
                    <ProductCover src={product.cover_asset} alt={product.name} />
                    <span className="absolute top-2 left-2 rounded-full bg-choc/80 px-2 py-1 font-sans text-[8px] tracking-[0.18em] text-gold uppercase">
                        {statusLabel(t, product.status)}
                    </span>
                </div>
                <div className="px-4 pt-3 pb-4">
                    <h3 className="mb-1 font-sans text-[9px] font-medium tracking-[0.2em] text-gold uppercase">
                        {product.name}
                    </h3>
                    {product.hero_subtitle ? (
                        <p className="mb-1.5 line-clamp-2 text-[13px] leading-[1.5] text-choc3">
                            {t(product.hero_subtitle)}
                        </p>
                    ) : null}
                    <p className="font-serif text-[18px] font-light text-choc lining-nums">
                        {`€ ${product.display_amount}`}
                    </p>
                </div>
            </MaisonLink>
        </Reveal>
    );
}
