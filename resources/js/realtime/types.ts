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
