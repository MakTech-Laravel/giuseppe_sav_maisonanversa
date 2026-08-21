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

type ProductPageData = {
    name: string;
    hero_subtitle: string;
    gallery: string[];
    specs: Array<{ label: string; value: string }>;
    includes: string[];
    guarantees: Array<{ icon: string; text: string }>;
};

type ProductFaq = {
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
    faqs: ProductFaq[];
    related: ProductCard[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Founding Edition · 100 Stuks Wereldwijd')}
                title={
                    <>
                        Heritage <em>No.001</em>
                    </>
                }
                subtitle={t(
                    product.hero_subtitle,
                )}
            />

            <ProductDetail edition={edition} product={product} />
            <ProductUnboxing />
            <ProductCraft />
            <ProductTrust />
            <ProductService />
            <ProductFaq faqs={faqs} />
            <ProductRelated related={related} />
        </>
    );
}
