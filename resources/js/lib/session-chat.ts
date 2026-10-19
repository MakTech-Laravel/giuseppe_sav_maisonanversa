import type { SessionCard } from '@/types/session';

/** The chat belongs to the host and the confirmed players. */
export function canOpenSessionChat(session: SessionCard): boolean {
    return session.is_host || session.is_joined;
}

/**
 * Posting stops once the session has ended (`ends_at` past) or is cancelled.
 * Held / unfinished past sessions stay read-only.
 */
export function isSessionChatOpen(session: SessionCard): boolean {
    if (session.is_cancelled || !session.ends_at) {
        return false;
    }

    return new Date(session.ends_at).getTime() > Date.now();
}
