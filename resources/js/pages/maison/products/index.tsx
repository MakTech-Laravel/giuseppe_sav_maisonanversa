import { useTranslation } from 'react-i18next';
import { ProductCatalogCard } from '@/components/maison/product/product-catalog-card';
import { ProductCatalogFilters } from '@/components/maison/product/product-catalog-filters';
import { ProductPagination } from '@/components/maison/product/product-pagination';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { ProductPaginator } from '@/types/product';

export default function ProductsIndex({
    products,
    filters,
}: {
    products: ProductPaginator;
    filters: { search: string; status: string };
}) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow="Maison Anversa"
                title={
                    <>
                        {t('De')} <em>{t('Producten')}</em>
                    </>
                }
                subtitle={t(
                    'De volledige collectie van Maison Anversa — van Heritage No.001 tot de volgende hoofdstukken van het huis.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <ProductCatalogFilters filters={filters} />

                    {products.data.length > 0 ? (
                        <div className="grid gap-10 ma-lg:grid-cols-3 md:grid-cols-2">
                            {products.data.map((product) => (
                                <ProductCatalogCard
                                    key={product.slug}
                                    product={product}
                                />
                            ))}
                        </div>
                    ) : (
                        <p className="py-16 text-center text-[15px] text-choc3">
                            {t('Geen producten gevonden.')}
                        </p>
                    )}

                    <ProductPagination products={products} />
                </Wrap>
            </Section>
        </>
    );
}
