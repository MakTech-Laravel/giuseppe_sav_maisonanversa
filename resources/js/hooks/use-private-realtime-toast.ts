import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { getEcho } from '@/echo';
import { useEchoGeneration } from '@/hooks/use-echo-connection';
import { privateUserChannel, REALTIME_TEST_EVENT } from '@/realtime/types';
import type { RealtimeTestPayload } from '@/realtime/types';

type Translate = (key: string) => string;

/** Stable toast id so Sonner collapses the same payload arriving twice. */
export function showRealtimeTestToast(
    payload: RealtimeTestPayload,
    t: Translate = (key) => key,
): void {
    const message = t(
        payload.message?.trim() || 'Realtime melding ontvangen.',
    );

    toast.success(message, {
        id:
            payload.triggered_at?.trim() ||
            `realtime-test-${payload.user_id ?? 'anon'}-${message}`,
        description: t('Privé realtime-kanaal'),
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
    const { t } = useTranslation();

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
        const onTest = (payload: RealtimeTestPayload) => {
            showRealtimeTestToast(payload, t);
        };

        channel.listen(event, onTest);

        return () => {
            try {
                channel.stopListening(event, onTest);
            } catch {
                // Echo may already be torn down during a reconnect.
            }
        };
    }, [userId, generation, t]);
}
