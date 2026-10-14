import { usePage } from '@inertiajs/react';
import { useInAppNotification } from '@/hooks/use-in-app-notification';
import { usePrivateRealtimeToast } from '@/hooks/use-private-realtime-toast';

/**
 * Mounted only in authenticated shells so anonymous visitors of the public
 * site never open a WebSocket to Reverb.
 */
export function RealtimeListeners() {
    const { auth } = usePage().props;

    usePrivateRealtimeToast(auth?.user?.id);
    useInAppNotification(auth?.user?.id);

    return null;
}
