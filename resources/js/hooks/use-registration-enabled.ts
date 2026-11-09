import { usePage } from '@inertiajs/react';

/**
 * False while the server has paused new sign-ups (env kill switch and/or Admin).
 * Reads the shared `registration` prop, which every page receives.
 */
export function useRegistrationEnabled(): boolean {
    const { registration } = usePage().props;

    return registration?.enabled !== false;
}
