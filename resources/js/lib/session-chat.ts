import type { SessionCard } from '@/types/session';

/**
 * Host and confirmed play seats always open chat. Staff (`isAdmin`) may open
 * any session from the public card (destination is admin chat).
 */
export function canOpenSessionChat(
    session: SessionCard,
    options?: { isAdmin?: boolean },
): boolean {
    if (options?.isAdmin) {
        return true;
    }

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
