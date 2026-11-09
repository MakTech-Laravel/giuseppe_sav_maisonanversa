import { usePage } from '@inertiajs/react';

/**
 * True while the server has paused purchases (env kill switch and/or Admin).
 * Reads the shared `checkout` prop, which every page receives.
 */
export function usePurchasesPaused(): boolean {
    const { checkout } = usePage().props;

    return checkout?.enabled === false;
}
