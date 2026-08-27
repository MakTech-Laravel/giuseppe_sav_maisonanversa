import { useTranslation } from 'react-i18next';
import { ProductGallery } from '@/components/maison/product/product-gallery';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useCheckoutDisplay } from '@/hooks/use-checkout-display';
import type { Edition } from '@/types/edition';
import type { ProductPageData } from '@/types/product';
import { productSectionItems } from '@/types/product';

export function ProductDetail({
    edition,
    product,
    checkout,
}: {
    edition: Edition;
    product: ProductPageData;
    /** Per-product checkout context; omitted falls back to the founding SKU. */
    checkout?: OrderProductContext;
}) {
    const { t } = useTranslation();
    const { openOrder, openNewsletter } = useShellActions();
    const { priceLabel, deliveryLabel } = useCheckoutDisplay(checkout);
    const isSoldOut = edition.soldOut || product.status === 'archived';
    const isComingSoon = product.status === 'coming_soon';
    const canReserve = !isSoldOut && !isComingSoon;
    const specs = productSectionItems(product.sections, 'specs');
    const guarantees = productSectionItems(product.sections, 'guarantees');

    return (
        <Section tone="cream" className="py-18">
            <Wrap>
                <div className="grid items-start gap-12 lg:grid-cols-2 lg:gap-20">
                    <ProductGallery
                        gallery={product.gallery}
                        productName={product.name}
                    />

                    <div>
                        {product.eyebrow ? (
                            <span className="mb-3 block font-sans text-[9px] tracking-[0.28em] text-gold uppercase">
                                {product.eyebrow}
                            </span>
                        ) : null}
                        <p className="mb-1.5 font-serif text-[clamp(28px,3.5vw,48px)] leading-[1.1] font-medium [&_em]:text-gold [&_em]:italic">
                            {product.name}
                        </p>
                        <div className="mb-1 font-serif text-[40px] leading-none font-light text-choc lining-nums">
                            {priceLabel}
                        </div>
                        <div className="mb-7 flex items-center gap-3">
                            <span
                                aria-hidden="true"
                                className="size-1.5 shrink-0 rounded-full bg-gold"
                            />
                            <p className="font-sans text-[9px] tracking-[0.2em] text-stone uppercase">
                                {edition.available} {t('Nummers nog')}{' '}
                                {t('beschikbaar')}
                                {deliveryLabel ? (
                                    <>
                                        {' '}
                                        · {deliveryLabel}
                                    </>
                                ) : null}
                            </p>
                        </div>
                        {product.description ? (
                            <p className="mb-7 border-b border-gold/20 pb-7 text-[15px] leading-[1.85] text-choc3">
                                {product.description}
                            </p>
                        ) : null}

                        {specs.length > 0 ? (
                            <div className="mb-8 flex flex-col">
                                {specs.map((spec) => (
                                    <div
                                        key={spec.id}
                                        className="flex items-baseline justify-between gap-4 border-b border-gold/12 py-3"
                                    >
                                        <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                                            {spec.title}
                                        </span>
                                        <span className="text-right font-serif text-[15px] font-medium text-choc">
                                            {spec.body}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : null}

                        {canReserve ? (
                            <MaisonButton
                                variant="filled"
                                block
                                onClick={() => openOrder(checkout)}
                                className="mb-3"
                            >
                                {`${t('Reserveer Uw Nummer —')} ${priceLabel}`}
                            </MaisonButton>
                        ) : (
                            <MaisonButton
                                variant="filled"
                                block
                                onClick={openNewsletter}
                                className="mb-3"
                            >
                                {t('Schrijf in voor Heritage Letter')}
                            </MaisonButton>
                        )}
                        {canReserve && (
                            <MaisonButton
                                variant="outlineChoc"
                                block
                                onClick={openNewsletter}
                            >
                                {t('Schrijf in voor Heritage Letter')}
                            </MaisonButton>
                        )}

                        {guarantees.length > 0 ? (
                            <div className="mt-6 grid grid-cols-1 gap-4 border-t border-gold/15 pt-6 sm:grid-cols-3">
                                {guarantees.map((item) => (
                                    <div key={item.id} className="text-center">
                                        <div
                                            aria-hidden="true"
                                            className="mb-1.5 text-lg text-gold2"
                                        >
                                            {item.icon}
                                        </div>
                                        <div className="font-sans text-[9px] leading-[1.5] tracking-[0.15em] text-stone uppercase">
                                            {item.title}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : null}
                    </div>
                </div>
            </Wrap>
        </Section>
    );
}
