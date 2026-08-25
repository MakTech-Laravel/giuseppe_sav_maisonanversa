import { usePage } from '@inertiajs/react';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';

/**
 * Checkout display for the order modal. Defaults to the shared Founding
 * Edition context; pass a product override (from a specific product's
 * detail page) to check out that product instead.
 */
export function useCheckoutDisplay(override?: OrderProductContext) {
    const { checkout, commerce } = usePage().props;
    const source = override ?? checkout;

    const productId = source?.productId ?? null;
    const amount = source?.amount ?? '';
    const displayAmount = source?.displayAmount ?? '';
    const currency = (source?.currency ?? 'eur').toUpperCase();
    const productName = source?.productName ?? '';
    const productType = source?.productType ?? 'limited_edition';
    const deliveryLabel =
        source?.deliveryLabel ??
        commerce?.defaultExpectedDeliveryLabel ??
        null;
    const priceLabel = displayAmount !== '' ? `€ ${displayAmount}` : '';

    return {
        productId,
        amount,
        displayAmount,
        currency,
        productName,
        productType,
        deliveryLabel,
        priceLabel,
        shippingEstimateMin: commerce?.shippingEstimateMin ?? '12.00',
        shippingEstimateMax: commerce?.shippingEstimateMax ?? '18.00',
        shippingEuIncluded: commerce?.shippingEuIncluded ?? true,
    };
}
