import { useEffect } from 'react';
import { getEcho } from '@/echo';
import { useEchoGeneration } from '@/hooks/use-echo-connection';
import {
    SESSION_MESSAGE_CREATED_EVENT,
    sessionChatChannel,
} from '@/realtime/types';
import type { ChatMessage } from '@/types/session-chat';

/**
 * Subscribes to `.message.created` on the session's private chat channel and
 * appends incoming messages, skipping ids already present (own send / Echo race).
 */
export function useSessionChatChannel(
    sessionId: number | string | null | undefined,
    onMessage: (message: ChatMessage) => void,
): void {
    const generation = useEchoGeneration();

    useEffect(() => {
        if (sessionId == null || sessionId === '') {
            return;
        }

        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channel = echo.private(sessionChatChannel(sessionId));
        const event = `.${SESSION_MESSAGE_CREATED_EVENT}`;

        const onCreated = (payload: ChatMessage) => {
            onMessage(payload);
        };

        channel.listen(event, onCreated);

        return () => {
            try {
                channel.stopListening(event, onCreated);
            } catch {
                // Echo may already be torn down during a reconnect.
            }
        };
    }, [sessionId, generation, onMessage]);
}
