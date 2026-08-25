import { Fragment, type ReactNode } from 'react';
import { ProductCraft } from '@/components/maison/product/product-craft';
import { ProductDetail } from '@/components/maison/product/product-detail';
import { ProductFaq } from '@/components/maison/product/product-faq';
import { ProductRelated } from '@/components/maison/product/product-related';
import { ProductService } from '@/components/maison/product/product-service';
import { ProductTrust } from '@/components/maison/product/product-trust';
import { ProductUnboxing } from '@/components/maison/product/product-unboxing';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';
import { PageHero } from '@/components/maison/ui/page-hero';
import type { Edition } from '@/types/edition';
import type {
    ProductCard,
    ProductPageData,
    ProductSection,
} from '@/types/product';

export type { ProductPageData } from '@/types/product';

/**
 * Sections rendered inside <ProductDetail> have no standalone block of their
 * own; the rest are emitted in the order the admin configured.
 */
const INLINE_SECTIONS = new Set(['specs', 'includes', 'guarantees']);

export default function ProductShow({
    productEdition,
    product,
    productCheckout,
    related,
}: {
    productEdition: Edition;
    product: ProductPageData;
    productCheckout: OrderProductContext;
    related: ProductCard[];
}) {
    const renderSection = (section: ProductSection): ReactNode => {
        switch (section.key) {
            case 'unboxing':
                return <ProductUnboxing section={section} />;
            case 'craft':
                return <ProductCraft section={section} />;
            case 'trust':
                return <ProductTrust section={section} />;
            case 'service':
                return <ProductService section={section} />;
            case 'faq':
                return <ProductFaq faqs={product.faqs} section={section} />;
            case 'related':
                return <ProductRelated related={related} section={section} />;
            default:
                return null;
        }
    };

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={product.hero_eyebrow || undefined}
                title={product.name}
                subtitle={product.hero_subtitle || undefined}
            />

            <ProductDetail
                edition={productEdition}
                product={product}
                checkout={productCheckout}
            />

            {product.sections
                .filter((section) => !INLINE_SECTIONS.has(section.key))
                .map((section) => (
                    <Fragment key={section.key}>
                        {renderSection(section)}
                    </Fragment>
                ))}
        </>
    );
}
