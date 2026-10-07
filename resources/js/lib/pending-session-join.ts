/** sessionStorage key for a join started before the guest authenticated. */
export const PENDING_SESSION_JOIN_KEY = 'pendingSessionJoinId';

export function storePendingSessionJoin(sessionId: string): void {
    sessionStorage.setItem(PENDING_SESSION_JOIN_KEY, sessionId);
}

export function takePendingSessionJoin(): string | null {
    const sessionId = sessionStorage.getItem(PENDING_SESSION_JOIN_KEY);

    if (sessionId === null) {
        return null;
    }

    sessionStorage.removeItem(PENDING_SESSION_JOIN_KEY);

    return sessionId;
}
