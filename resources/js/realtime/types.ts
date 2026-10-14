export type ConnectionStatus =
    | 'disabled'
    | 'idle'
    | 'connecting'
    | 'connected'
    | 'unavailable'
    | 'disconnected'
    | 'failed';

export const CONNECTION_STATUS_LABELS: Record<ConnectionStatus, string> = {
    disabled: 'Uitgeschakeld',
    idle: 'Inactief',
    connecting: 'Verbinden…',
    connected: 'Verbonden',
    unavailable: 'Niet beschikbaar',
    disconnected: 'Verbroken',
    failed: 'Mislukt',
};

export const REVERB_PING_CHANNEL = 'reverb-ping';
export const REVERB_PING_EVENT = 'ping';
export const REALTIME_TEST_EVENT = 'realtime.test';
export const NOTIFICATION_CREATED_EVENT = 'notification.created';

/** Window event re-emitted for every in-app notification received over Echo. */
export const NOTIFICATION_RECEIVED_WINDOW_EVENT = 'app-notification:received';

export function privateUserChannel(userId: number): string {
    return `App.Models.User.${userId}`;
}

export type ReverbPingPayload = {
    message?: string;
    triggered_at?: string;
    source?: string;
};

export type RealtimeTestPayload = {
    message?: string;
    triggered_at?: string;
    user_id?: number;
};
