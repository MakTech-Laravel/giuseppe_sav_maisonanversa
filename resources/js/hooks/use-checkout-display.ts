import { usePage } from '@inertiajs/react';

/**
 * Shared Founding Edition checkout display from the products table.
 */
export function useCheckoutDisplay() {
    const { checkout, commerce } = usePage().props;

    const productId = checkout?.productId ?? null;
    const amount = checkout?.amount ?? '';
    const displayAmount = checkout?.displayAmount ?? '';
    const currency = (checkout?.currency ?? 'eur').toUpperCase();
    const productName = checkout?.productName ?? '';
    const deliveryLabel =
        checkout?.deliveryLabel ??
        commerce?.defaultExpectedDeliveryLabel ??
        null;
    const priceLabel = displayAmount !== '' ? `€ ${displayAmount}` : '';

    return {
        productId,
        amount,
        displayAmount,
        currency,
        productName,
        deliveryLabel,
        priceLabel,
        shippingEstimateMin: commerce?.shippingEstimateMin ?? '12.00',
        shippingEstimateMax: commerce?.shippingEstimateMax ?? '18.00',
        shippingEuIncluded: commerce?.shippingEuIncluded ?? true,
    };
}
