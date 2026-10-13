import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'> | undefined;
    }
}

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

export type AppEcho = Echo<'reverb'>;

export type ReverbClientConfig = {
    enabled: boolean;
    appKey: string;
    host: string;
    port: number;
    scheme: string;
    useTls: boolean;
    enabledTransports: Array<'ws' | 'wss'>;
    authEndpoint: string;
};

export function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function getReverbClientConfig(): ReverbClientConfig {
    const appKey = String(import.meta.env.VITE_REVERB_APP_KEY ?? '').trim();
    const host = String(import.meta.env.VITE_REVERB_HOST ?? '').trim();
    const scheme = String(import.meta.env.VITE_REVERB_SCHEME ?? 'https').trim();
    const useTls = scheme === 'https' || scheme === 'wss';
    const port = Number(
        import.meta.env.VITE_REVERB_PORT ?? (useTls ? 443 : 80),
    );

    return {
        enabled: appKey !== '' && host !== '',
        appKey,
        host,
        port: Number.isFinite(port) && port > 0 ? port : useTls ? 443 : 80,
        scheme,
        useTls,
        // pusher-js names its primary WebSocket transport "ws" even under forceTLS
        // (it still dials wss://). Enabling only "wss" leaves just the XHR/SockJS
        // fallbacks, which Reverb does not speak.
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    };
}

/**
 * Browser-only: never instantiate during Inertia SSR. Private channel auth
 * rides the Fortify session cookie plus the XSRF-TOKEN cookie.
 */
export function createEcho(): AppEcho | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const config = getReverbClientConfig();

    if (!config.enabled) {
        return null;
    }

    window.Pusher = Pusher;

    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    // Read lazily: the XSRF cookie rotates after login/logout.
    Object.defineProperty(headers, 'X-XSRF-TOKEN', {
        get: getXsrfToken,
        enumerable: true,
        configurable: true,
    });

    return new Echo({
        broadcaster: 'reverb',
        key: config.appKey,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: config.useTls,
        enabledTransports: config.enabledTransports,
        disableStats: true,
        authEndpoint: config.authEndpoint,
        Pusher,
        auth: { headers },
        withCredentials: true,
    });
}

let echoGeneration = 0;

let echoInstance: AppEcho | null | undefined;

/** Bumped whenever the singleton is torn down so subscribers can re-subscribe. */
export function getEchoGeneration(): number {
    return echoGeneration;
}

export function getEcho(): AppEcho | null {
    if (echoInstance === undefined) {
        echoInstance = createEcho();

        if (typeof window !== 'undefined') {
            window.Echo = echoInstance ?? undefined;
        }
    }

    return echoInstance;
}

export function disconnectEcho(): void {
    echoInstance?.disconnect();
    echoInstance = undefined;
    echoGeneration += 1;

    if (typeof window !== 'undefined') {
        window.Echo = undefined;
    }
}

/** pusher-js stops retrying once "failed"; this opens a fresh socket. */
export function reconnectEcho(): AppEcho | null {
    disconnectEcho();
    const echo = getEcho();

    if (typeof window !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('echo:reconnected', {
                detail: { generation: echoGeneration },
            }),
        );
    }

    return echo;
}

export function logEchoConfig(): void {
    if (!import.meta.env.DEV) {
        return;
    }

    const config = getReverbClientConfig();

    console.info('[echo] config', {
        enabled: config.enabled,
        appKey: config.appKey ? `${config.appKey.slice(0, 8)}...` : 'MISSING',
        host: config.host || 'MISSING',
        port: config.port,
        scheme: config.scheme,
        forceTLS: config.useTls,
        enabledTransports: config.enabledTransports,
        authEndpoint: config.authEndpoint,
    });
}
