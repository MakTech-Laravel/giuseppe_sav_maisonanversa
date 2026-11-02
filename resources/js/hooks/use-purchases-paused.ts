import { usePage } from '@inertiajs/react';

/**
 * True while the server has paused purchases (MAISON_CHECKOUT_ENABLED=false).
 * Reads the shared `checkout` prop, which every page receives.
 */
export function usePurchasesPaused(): boolean {
    const { checkout } = usePage().props;

    return checkout?.enabled === false;
}
