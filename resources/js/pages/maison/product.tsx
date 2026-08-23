import { useTranslation } from 'react-i18next';
import { ProductCraft } from '@/components/maison/product/product-craft';
import { ProductDetail } from '@/components/maison/product/product-detail';
import { ProductFaq } from '@/components/maison/product/product-faq';
import { ProductRelated } from '@/components/maison/product/product-related';
import { ProductService } from '@/components/maison/product/product-service';
import { ProductTrust } from '@/components/maison/product/product-trust';
import { ProductUnboxing } from '@/components/maison/product/product-unboxing';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import type { Edition } from '@/types/edition';

export type ProductPageData = {
    name: string;
    eyebrow: string;
    hero_eyebrow: string;
    hero_subtitle: string;
    description: string;
    gallery: string[];
    specs: Array<{ label: string; value: string }>;
    materials: Array<{ num: string; name: string; desc: string }>;
    unboxing_steps: Array<{ num: string; title: string; desc: string }>;
    includes: string[];
    guarantees: Array<{ icon: string; text: string }>;
    trust_badges: Array<{ icon: string; text: string }>;
    edition_total: number | null;
};

type ProductFaqItem = {
    question: string;
    answer: string;
};

type ProductCard = {
    name: string;
    status: string;
    hero_subtitle: string;
    cover_asset: string | null;
};

export default function Product({
    edition,
    product,
    faqs,
    related,
}: {
    edition: Edition;
    product: ProductPageData;
    faqs: ProductFaqItem[];
    related: ProductCard[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={
                    product.hero_eyebrow
                        ? t(product.hero_eyebrow)
                        : undefined
                }
                title={product.name}
                subtitle={
                    product.hero_subtitle
                        ? t(product.hero_subtitle)
                        : undefined
                }
            />

            <ProductDetail edition={edition} product={product} />
            <ProductUnboxing steps={product.unboxing_steps} />
            <ProductCraft materials={product.materials} />
            <ProductTrust badges={product.trust_badges} />
            <ProductService />
            <ProductFaq faqs={faqs} />
            <ProductRelated related={related} />
        </>
    );
}
