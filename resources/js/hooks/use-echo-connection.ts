import { useCallback, useEffect, useState, useSyncExternalStore } from 'react';
import {
    getEcho,
    getEchoGeneration,
    getReverbClientConfig,
    reconnectEcho,
} from '@/echo';
import type { AppEcho } from '@/echo';
import type { ConnectionStatus } from '@/realtime/types';

type PusherConnection = {
    state?: string;
    socket_id?: string;
    bind: (event: string, cb: (...args: unknown[]) => void) => void;
    unbind: (event: string, cb?: (...args: unknown[]) => void) => void;
};

function pusherConnection(echo: AppEcho | null): PusherConnection | undefined {
    const connector = echo?.connector as
        | { pusher?: { connection?: PusherConnection } }
        | undefined;

    return connector?.pusher?.connection;
}

function stringify(value: unknown): string {
    if (value == null) {
        return '';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    try {
        return JSON.stringify(value) ?? '';
    } catch {
        return Object.prototype.toString.call(value);
    }
}

/** Flattens the nested pusher-js error envelopes into "message (code N)". */
function formatConnectionError(payload: unknown): string {
    if (payload == null) {
        return 'Unknown connection error';
    }

    if (typeof payload !== 'object') {
        return stringify(payload);
    }

    const record = payload as Record<string, unknown>;
    const nested =
        record.error && typeof record.error === 'object'
            ? (record.error as Record<string, unknown>)
            : record;

    let data: Record<string, unknown> = nested;

    if (typeof nested.data === 'string') {
        try {
            data = JSON.parse(nested.data) as Record<string, unknown>;
        } catch {
            data = { message: nested.data };
        }
    } else if (typeof nested.data === 'object' && nested.data !== null) {
        data = nested.data as Record<string, unknown>;
    }

    const code = stringify(data.code ?? nested.code ?? record.code);
    const message =
        stringify(data.message ?? nested.message ?? record.message) ||
        'Connection error';

    return code !== '' ? `${message} (code ${code})` : message;
}

function readStatus(echo: AppEcho | null): ConnectionStatus {
    if (!echo) {
        return getReverbClientConfig().enabled ? 'idle' : 'disabled';
    }

    switch (pusherConnection(echo)?.state) {
        case 'connecting':
        case 'connected':
        case 'unavailable':
        case 'failed':
        case 'disconnected':
            return pusherConnection(echo)?.state as ConnectionStatus;
        default:
            return 'idle';
    }
}

function subscribeToGeneration(onChange: () => void): () => void {
    window.addEventListener('echo:reconnected', onChange);

    return () => window.removeEventListener('echo:reconnected', onChange);
}

/** Follows the pusher connection across reconnects (each makes a new socket). */
function subscribeToConnection(onChange: () => void): () => void {
    let connection = pusherConnection(getEcho());

    const rebind = () => {
        connection?.unbind('state_change', onChange);
        connection = pusherConnection(getEcho());
        connection?.bind('state_change', onChange);
        onChange();
    };

    connection?.bind('state_change', onChange);
    window.addEventListener('echo:reconnected', rebind);

    return () => {
        connection?.unbind('state_change', onChange);
        window.removeEventListener('echo:reconnected', rebind);
    };
}

function connectionSnapshot(): string {
    const echo = getEcho();

    return `${readStatus(echo)}|${pusherConnection(echo)?.socket_id ?? ''}`;
}

/** Re-render subscribers whenever the Echo singleton is recreated. */
export function useEchoGeneration(): number {
    return useSyncExternalStore(
        subscribeToGeneration,
        getEchoGeneration,
        () => 0,
    );
}

export function useEchoConnection() {
    const config = getReverbClientConfig();
    const generation = useEchoGeneration();
    const snapshot = useSyncExternalStore(
        subscribeToConnection,
        connectionSnapshot,
        () => 'disabled|',
    );
    const [status, socketId] = snapshot.split('|') as [
        ConnectionStatus,
        string,
    ];
    const [lastError, setLastError] = useState<string | null>(null);

    useEffect(() => {
        const connection = pusherConnection(getEcho());

        if (!connection) {
            return;
        }

        const onConnected = () => setLastError(null);
        const onError = (...args: unknown[]) =>
            setLastError(formatConnectionError(args[0]));

        connection.bind('connected', onConnected);
        connection.bind('error', onError);

        return () => {
            connection.unbind('connected', onConnected);
            connection.unbind('error', onError);
        };
    }, [generation]);

    const reconnect = useCallback(() => {
        setLastError(null);
        reconnectEcho();
    }, []);

    return {
        status,
        socketId: socketId === '' ? null : socketId,
        lastError,
        enabled: config.enabled,
        config,
        generation,
        reconnect,
    };
}
