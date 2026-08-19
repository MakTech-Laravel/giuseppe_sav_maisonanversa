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

export default function Product({ edition }: { edition: Edition }) {
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
                    'Het eerste hoofdstuk van Maison Anversa. Beperkt tot 100 stuks. Elk genummerd. De Founding Edition wordt nooit herhaald.',
                )}
            />

            <ProductDetail edition={edition} />
            <ProductUnboxing />
            <ProductCraft />
            <ProductTrust />
            <ProductService />
            <ProductFaq />
            <ProductRelated />
        </>
    );
}
