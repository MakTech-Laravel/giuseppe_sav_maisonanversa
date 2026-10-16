/** Which session chat transcript is open in this tab (null = none). */
let watchingSessionId: number | null = null;

export function setWatchingSessionChatId(
    sessionId: number | string | null,
): void {
    watchingSessionId =
        sessionId == null || sessionId === '' ? null : Number(sessionId);
}

export function getWatchingSessionChatId(): number | null {
    return watchingSessionId;
}
