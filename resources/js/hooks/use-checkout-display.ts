import { usePage } from '@inertiajs/react';

/**
 * Shared Founding Edition checkout display from the products table.
 */
export function useCheckoutDisplay() {
    const { checkout } = usePage().props;

    const amount = checkout?.amount ?? '249.00';
    const displayAmount = checkout?.displayAmount ?? '249,00';
    const currency = (checkout?.currency ?? 'eur').toUpperCase();
    const productName =
        checkout?.productName ?? 'Heritage No.001 — Founding Edition';
    const priceLabel = `€ ${displayAmount}`;

    return {
        amount,
        displayAmount,
        currency,
        productName,
        priceLabel,
    };
}
