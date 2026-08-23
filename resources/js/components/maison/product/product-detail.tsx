import { useTranslation } from 'react-i18next';
import { ProductGallery } from '@/components/maison/product/product-gallery';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useCheckoutDisplay } from '@/hooks/use-checkout-display';
import type { Edition } from '@/types/edition';
import type { ProductPageData } from '@/pages/maison/product';

export function ProductDetail({
    edition,
    product,
}: {
    edition: Edition;
    product: ProductPageData;
}) {
    const { t } = useTranslation();
    const { openOrder, openNewsletter } = useShellActions();
    const { priceLabel, deliveryLabel } = useCheckoutDisplay();

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
                                {t(product.eyebrow)}
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
                                {t(product.description)}
                            </p>
                        ) : null}

                        {product.specs.length > 0 ? (
                            <div className="mb-8 flex flex-col">
                                {product.specs.map((spec) => (
                                    <div
                                        key={spec.label}
                                        className="flex items-baseline justify-between gap-4 border-b border-gold/12 py-3"
                                    >
                                        <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                                            {t(spec.label)}
                                        </span>
                                        <span className="text-right font-serif text-[15px] font-medium text-choc">
                                            {spec.value === '3K Carbon' ||
                                            spec.value === 'EVA Soft'
                                                ? spec.value
                                                : t(spec.value)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : null}

                        {edition.soldOut ? (
                            <MaisonButton
                                variant="filled"
                                block
                                onClick={openNewsletter}
                                className="mb-3"
                            >
                                {t('Schrijf in voor Heritage Letter')}
                            </MaisonButton>
                        ) : (
                            <MaisonButton
                                variant="filled"
                                block
                                onClick={openOrder}
                                className="mb-3"
                            >
                                {`${t('Reserveer Uw Nummer —')} ${priceLabel}`}
                            </MaisonButton>
                        )}
                        {!edition.soldOut && (
                            <MaisonButton
                                variant="outlineChoc"
                                block
                                onClick={openNewsletter}
                            >
                                {t('Schrijf in voor Heritage Letter')}
                            </MaisonButton>
                        )}

                        {product.guarantees.length > 0 ? (
                            <div className="mt-6 grid grid-cols-1 gap-4 border-t border-gold/15 pt-6 sm:grid-cols-3">
                                {product.guarantees.map((item) => (
                                    <div
                                        key={item.text}
                                        className="text-center"
                                    >
                                        <div
                                            aria-hidden="true"
                                            className="mb-1.5 text-lg text-gold2"
                                        >
                                            {item.icon}
                                        </div>
                                        <div className="font-sans text-[9px] leading-[1.5] tracking-[0.15em] text-stone uppercase">
                                            {t(item.text)}
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
