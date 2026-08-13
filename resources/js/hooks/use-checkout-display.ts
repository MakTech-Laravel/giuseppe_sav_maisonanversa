import { usePage } from '@inertiajs/react';

/**
 * Shared Founding Edition checkout display from config/maison.php.
 */
export function useCheckoutDisplay() {
    const { checkout } = usePage().props;

    const amountCents = checkout?.amount ?? 24900;
    const displayAmount = checkout?.displayAmount ?? '249';
    const currency = (checkout?.currency ?? 'eur').toUpperCase();
    const productName =
        checkout?.productName ?? 'Heritage No.001 — Founding Edition';
    const priceLabel = `€ ${displayAmount}`;

    return {
        amountCents,
        displayAmount,
        currency,
        productName,
        priceLabel,
    };
}
