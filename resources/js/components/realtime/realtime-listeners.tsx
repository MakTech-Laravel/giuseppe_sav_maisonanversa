import { usePage } from '@inertiajs/react';
import { useInAppNotification } from '@/hooks/use-in-app-notification';
import { usePresenceHeartbeat } from '@/hooks/use-presence-heartbeat';
import { usePrivateRealtimeToast } from '@/hooks/use-private-realtime-toast';
import { useSessionChatUnread } from '@/hooks/use-session-chat-unread';

/**
 * Mounted only in authenticated shells so anonymous visitors of the public
 * site never open a WebSocket to Reverb.
 */
export function RealtimeListeners() {
    const { auth } = usePage().props;

    usePresenceHeartbeat();
    usePrivateRealtimeToast(auth?.user?.id);
    useInAppNotification(auth?.user?.id);
    useSessionChatUnread(auth?.user?.id);

    return null;
}
