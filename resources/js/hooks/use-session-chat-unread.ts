import { router } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { createElement, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { getEcho } from '@/echo';
import { useEchoGeneration } from '@/hooks/use-echo-connection';
import { getWatchingSessionChatId } from '@/lib/watching-session-chat';
import {
    SESSION_CHAT_ACTIVITY_EVENT,
    SESSION_CHAT_ACTIVITY_WINDOW_EVENT,
    privateUserChannel,
} from '@/realtime/types';

export type SessionChatActivityPayload = {
    session_id: number;
    unread_total: number;
};

/**
 * Listens for `.session.chat.activity` on the private user channel and refreshes
 * shared chat unread badges. When the member is active but not watching that
 * session's chat, shows a live toast (never an inbox/email — digests handle offline).
 */
export function useSessionChatUnread(userId?: number | null): void {
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
        const event = `.${SESSION_CHAT_ACTIVITY_EVENT}`;

        const onActivity = (payload: SessionChatActivityPayload) => {
            window.dispatchEvent(
                new CustomEvent<SessionChatActivityPayload>(
                    SESSION_CHAT_ACTIVITY_WINDOW_EVENT,
                    { detail: payload },
                ),
            );

            const watchingId = getWatchingSessionChatId();
            const watchingThisChat =
                watchingId !== null &&
                Number(watchingId) === Number(payload.session_id);

            if (!watchingThisChat) {
                toast(t('Nieuw bericht'), {
                    id: `session-chat-${payload.session_id}`,
                    icon: createElement(MessageCircle, {
                        className: 'size-4 text-gold',
                    }),
                    richColors: false,
                    duration: 5000,
                    style: {
                        background: 'var(--color-choc)',
                        color: '#fff',
                        borderColor: 'var(--color-gold)',
                    },
                    classNames: {
                        toast: 'border-l-4 shadow-xl',
                        closeButton: '!bg-choc2 !text-white !border-gold/40',
                    },
                });
            }

            router.reload({
                only: ['chat', 'unread_counts'],
                preserveScroll: true,
                preserveState: true,
            });
        };

        channel.listen(event, onActivity);

        return () => {
            try {
                channel.stopListening(event, onActivity);
            } catch {
                // Echo may already be torn down during a reconnect.
            }
        };
    }, [userId, generation, t]);
}
