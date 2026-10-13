import { usePage } from '@inertiajs/react';
import { usePrivateRealtimeToast } from '@/hooks/use-private-realtime-toast';

/**
 * Mounted only in authenticated shells so anonymous visitors of the public
 * site never open a WebSocket to Reverb.
 */
export function RealtimeListeners() {
    const { auth } = usePage().props;

    usePrivateRealtimeToast(auth?.user?.id);

    return null;
}
