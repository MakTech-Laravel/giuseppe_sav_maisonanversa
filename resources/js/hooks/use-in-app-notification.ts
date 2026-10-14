import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { showNotificationToast } from '@/components/notifications/notification-toast';
import { getEcho } from '@/echo';
import { useEchoGeneration } from '@/hooks/use-echo-connection';
import {
    NOTIFICATION_CREATED_EVENT,
    NOTIFICATION_RECEIVED_WINDOW_EVENT,
    privateUserChannel,
} from '@/realtime/types';
import type { NotificationItem } from '@/types/notification';

/**
 * Surfaces `.notification.created` broadcasts: toast, refresh the shared
 * `notifications` prop (bells), and re-emit on window for pages that list
 * notifications. Never leaves the shared private channel.
 */
export function useInAppNotification(userId?: number | null): void {
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
        const event = `.${NOTIFICATION_CREATED_EVENT}`;

        const onCreated = (payload: NotificationItem) => {
            showNotificationToast(payload, t);
            router.reload({ only: ['notifications'] });
            window.dispatchEvent(
                new CustomEvent<NotificationItem>(
                    NOTIFICATION_RECEIVED_WINDOW_EVENT,
                    { detail: payload },
                ),
            );
        };

        channel.listen(event, onCreated);

        return () => {
            try {
                channel.stopListening(event, onCreated);
            } catch {
                // Echo may already be torn down during a reconnect.
            }
        };
    }, [userId, generation, t]);
}
