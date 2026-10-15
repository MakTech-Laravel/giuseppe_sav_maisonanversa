import type { SessionCard } from '@/types/session';

/** The chat belongs to the host and the confirmed players. */
export function canOpenSessionChat(session: SessionCard): boolean {
    return session.is_host || session.is_joined;
}

/** Posting stops once the session is finished or cancelled. */
export function isSessionChatOpen(session: SessionCard): boolean {
    return !session.is_cancelled && session.lifecycle !== 'finished';
}
