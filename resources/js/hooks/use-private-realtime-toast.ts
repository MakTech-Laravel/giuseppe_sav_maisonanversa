import { useEffect } from 'react';
import { toast } from 'sonner';
import { getEcho } from '@/echo';
import { useEchoGeneration } from '@/hooks/use-echo-connection';
import { privateUserChannel, REALTIME_TEST_EVENT } from '@/realtime/types';
import type { RealtimeTestPayload } from '@/realtime/types';

/** Stable toast id so Sonner collapses the same payload arriving twice. */
export function showRealtimeTestToast(payload: RealtimeTestPayload): void {
    const message =
        payload.message?.trim() || 'Realtime notification received.';

    toast.success(message, {
        id:
            payload.triggered_at?.trim() ||
            `realtime-test-${payload.user_id ?? 'anon'}-${message}`,
        description: 'Private realtime channel',
        duration: 6000,
    });
}

/**
 * Keeps the authenticated user's private channel subscribed and surfaces
 * `realtime.test` payloads as toasts. Never leaves the channel: other
 * listeners (e.g. the Realtime console) share the same subscription.
 */
export function usePrivateRealtimeToast(userId?: number | null): void {
    const generation = useEchoGeneration();

    useEffect(() => {
        if (!userId) {
            return;
        }

        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channel = echo.private(privateUserChannel(userId));
        const event = `.${REALTIME_TEST_EVENT}`;

        channel.listen(event, showRealtimeTestToast);

        return () => {
            try {
                channel.stopListening(event, showRealtimeTestToast);
            } catch {
                // Echo may already be torn down during a reconnect.
            }
        };
    }, [userId, generation]);
}
