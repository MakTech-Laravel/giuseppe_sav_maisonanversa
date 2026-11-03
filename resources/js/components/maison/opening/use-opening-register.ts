import { usePage } from '@inertiajs/react';
import type { OpeningRegisterData } from '@/types/opening';

/**
 * The register the home page was sent with: the admin-chosen product and its
 * edition numbers, or null when there is nothing to offer.
 */
export function useOpeningRegister(): OpeningRegisterData | null {
    const { opening } = usePage().props;

    return (opening as OpeningRegisterData | null | undefined) ?? null;
}
