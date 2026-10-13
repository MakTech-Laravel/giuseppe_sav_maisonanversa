import { Head, usePage } from '@inertiajs/react';
import {
    Bell,
    Eraser,
    Radio,
    RefreshCw,
    Send,
    ServerCog,
    ShieldCheck,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { getEcho } from '@/echo';
import { useEchoConnection } from '@/hooks/use-echo-connection';
import { showRealtimeTestToast } from '@/hooks/use-private-realtime-toast';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { callJson } from '@/realtime/call-json';
import {
    CONNECTION_STATUS_LABELS,
    privateUserChannel,
    REALTIME_TEST_EVENT,
    REVERB_PING_CHANNEL,
    REVERB_PING_EVENT,
} from '@/realtime/types';
import type {
    ConnectionStatus,
    RealtimeTestPayload,
    ReverbPingPayload,
} from '@/realtime/types';
import { dashboard } from '@/routes/admin';
import realtime from '@/routes/admin/realtime';

type LogLevel = 'info' | 'success' | 'warn' | 'error';

type LogEntry = { id: number; level: LogLevel; message: string; ts: string };

type ReceivedEvent = {
    id: number;
    channel: string;
    event: string;
    data: unknown;
    ts: string;
};

type ApiResult = { ok: boolean; body: string };

const STATUS_DOT: Record<ConnectionStatus, string> = {
    connected: 'bg-emerald-500',
    connecting: 'bg-amber-500 animate-pulse',
    unavailable: 'bg-orange-500',
    disconnected: 'bg-orange-500',
    failed: 'bg-red-500',
    idle: 'bg-muted-foreground/50',
    disabled: 'bg-muted-foreground/50',
};

const LOG_COLOR: Record<LogLevel, string> = {
    info: 'text-sky-600 dark:text-sky-400',
    success: 'text-emerald-600 dark:text-emerald-400',
    warn: 'text-amber-600 dark:text-amber-400',
    error: 'text-red-600 dark:text-red-400',
};

function clock(): string {
    return new Date().toLocaleTimeString('en-GB', { hour12: false });
}

export default function RealtimeIndex() {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const userId = auth?.user?.id;
    const {
        status,
        socketId,
        enabled,
        config,
        lastError,
        generation,
        reconnect,
    } = useEchoConnection();

    const [events, setEvents] = useState<ReceivedEvent[]>([]);
    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [apiResult, setApiResult] = useState<ApiResult | null>(null);
    const [busy, setBusy] = useState(false);
    const [publicSubscribed, setPublicSubscribed] = useState(false);
    const [privateSubscribed, setPrivateSubscribed] = useState(false);
    const nextId = useRef(0);

    const addLog = useCallback((level: LogLevel, message: string) => {
        setLogs((prev) => [
            ...prev.slice(-149),
            { id: nextId.current++, level, message, ts: clock() },
        ]);
    }, []);

    const addEvent = useCallback(
        (channel: string, event: string, data: unknown) => {
            setEvents((prev) =>
                [
                    { id: nextId.current++, channel, event, data, ts: clock() },
                    ...prev,
                ].slice(0, 50),
            );
        },
        [],
    );

    useEffect(() => {
        if (lastError) {
            addLog('error', lastError);
        }
    }, [lastError, addLog]);

    useEffect(() => {
        addLog('info', `${t('Status')}: ${CONNECTION_STATUS_LABELS[status]}`);
    }, [status, addLog, t]);

    useEffect(() => {
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channel = echo.channel(REVERB_PING_CHANNEL);
        const event = `.${REVERB_PING_EVENT}`;
        const onPing = (data: ReverbPingPayload) => {
            addEvent(REVERB_PING_CHANNEL, REVERB_PING_EVENT, data);
            addLog('success', `Public event received: ${data.message ?? ''}`);
        };

        channel
            .subscribed(() => {
                setPublicSubscribed(true);
                addLog(
                    'success',
                    `Subscribed to public "${REVERB_PING_CHANNEL}"`,
                );
            })
            .error((error: unknown) => {
                setPublicSubscribed(false);
                addLog(
                    'error',
                    `Public subscription error: ${JSON.stringify(error)}`,
                );
            })
            .listen(event, onPing);

        return () => {
            try {
                channel.stopListening(event, onPing);
            } catch {
                // Echo may already be torn down during a reconnect.
            }

            echo.leave(REVERB_PING_CHANNEL);
            setPublicSubscribed(false);
        };
    }, [generation, addEvent, addLog]);

    useEffect(() => {
        const echo = getEcho();

        if (!echo || !userId) {
            return;
        }

        const channelName = privateUserChannel(userId);
        const channel = echo.private(channelName);
        const event = `.${REALTIME_TEST_EVENT}`;
        const onTest = (data: RealtimeTestPayload) => {
            addEvent(`private-${channelName}`, REALTIME_TEST_EVENT, data);
            addLog(
                'success',
                `Private event received: ${data.message ?? 'ok'}`,
            );
        };

        channel
            .subscribed(() => {
                setPrivateSubscribed(true);
                addLog(
                    'success',
                    `Authorized + subscribed to "private-${channelName}"`,
                );
            })
            .error((error: unknown) => {
                setPrivateSubscribed(false);
                addLog(
                    'error',
                    `Private channel auth failed (/broadcasting/auth): ${JSON.stringify(error)}`,
                );
            })
            .listen(event, onTest);

        return () => {
            try {
                channel.stopListening(event, onTest);
            } catch {
                // Echo may already be torn down during a reconnect.
            }

            // Never leave(): the global toast listener shares this channel.
            setPrivateSubscribed(false);
        };
    }, [generation, userId, addEvent, addLog]);

    const run = useCallback(
        async (
            label: string,
            url: string,
            method: 'get' | 'post',
            body?: Record<string, unknown>,
        ) => {
            setBusy(true);
            addLog('info', `${method.toUpperCase()} ${url}`);

            try {
                const {
                    ok,
                    status: code,
                    json,
                } = await callJson(url, method, body);

                setApiResult({ ok, body: JSON.stringify(json, null, 2) });

                if (!ok) {
                    throw new Error(String(json.message ?? `HTTP ${code}`));
                }

                addLog('success', `${label}: ${String(json.message ?? 'OK')}`);
            } catch (err) {
                const message =
                    err instanceof Error ? err.message : String(err);

                setApiResult((prev) =>
                    prev && !prev.ok ? prev : { ok: false, body: message },
                );
                addLog('error', `${label} failed: ${message}`);
            } finally {
                setBusy(false);
            }
        },
        [addLog],
    );

    const locale = wayfinderLocale();
    const connected = status === 'connected';

    return (
        <>
            <Head title={t('Realtime console')} />

            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Realtime console')}
                    description={t(
                        'End-to-end test: Maison Anversa → Reverb server → browser (Echo).',
                    )}
                    icon={Radio}
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex flex-wrap items-center gap-3">
                            <span
                                className={cn(
                                    'h-3 w-3 rounded-full',
                                    STATUS_DOT[status],
                                )}
                            />
                            {t(CONNECTION_STATUS_LABELS[status])}
                            {socketId && (
                                <Badge
                                    variant="secondary"
                                    className="font-mono"
                                >
                                    socket_id: {socketId}
                                </Badge>
                            )}
                            <Badge
                                variant={
                                    publicSubscribed ? 'default' : 'outline'
                                }
                            >
                                {REVERB_PING_CHANNEL}
                            </Badge>
                            <Badge
                                variant={
                                    privateSubscribed ? 'default' : 'outline'
                                }
                                className="gap-1"
                            >
                                <ShieldCheck className="h-3 w-3" />
                                {t('privé kanaal')}
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            {!enabled
                                ? t(
                                      'Echo is uitgeschakeld: VITE_REVERB_APP_KEY of VITE_REVERB_HOST ontbreekt in de build.',
                                  )
                                : t(
                                      'Na "Mislukt" stopt pusher-js met opnieuw proberen. Klik op Opnieuw verbinden zodra de Reverb-server weer draait.',
                                  )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {lastError && (
                            <p className="rounded-md border border-red-500/30 bg-red-500/5 px-3 py-2 font-mono text-xs text-red-600 dark:text-red-400">
                                {lastError}
                            </p>
                        )}

                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!enabled}
                                onClick={() => {
                                    addLog(
                                        'info',
                                        'Manual reconnect requested',
                                    );
                                    reconnect();
                                }}
                            >
                                <RefreshCw className="h-4 w-4" />
                                {t('Opnieuw verbinden')}
                            </Button>
                            <Button
                                size="sm"
                                disabled={busy || !connected}
                                onClick={() =>
                                    run(
                                        'Public ping',
                                        realtime.ping.url(locale),
                                        'post',
                                        { message: `Ping ${clock()}` },
                                    )
                                }
                            >
                                <Send className="h-4 w-4" />
                                {t('Publieke ping versturen')}
                            </Button>
                            <Button
                                size="sm"
                                variant="secondary"
                                disabled={busy || !connected || !userId}
                                onClick={() =>
                                    run(
                                        'Private test',
                                        realtime.testNotification.url(locale),
                                        'post',
                                    )
                                }
                            >
                                <ShieldCheck className="h-4 w-4" />
                                {t('Privé test versturen')}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => {
                                    showRealtimeTestToast({
                                        message: `Local toast test ${clock()}`,
                                        triggered_at: new Date().toISOString(),
                                        user_id: userId,
                                    });
                                    addLog('info', 'Local toast requested');
                                }}
                            >
                                <Bell className="h-4 w-4" />
                                {t('Toast testen')}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={busy}
                                onClick={() =>
                                    run(
                                        'Backend config',
                                        realtime.health.url(locale),
                                        'get',
                                    )
                                }
                            >
                                <ServerCog className="h-4 w-4" />
                                {t('Backend-configuratie')}
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => {
                                    setEvents([]);
                                    setLogs([]);
                                    setApiResult(null);
                                }}
                            >
                                <Eraser className="h-4 w-4" />
                                {t('Wissen')}
                            </Button>
                        </div>

                        <dl className="grid grid-cols-1 gap-x-6 gap-y-1 border-t pt-3 font-mono text-xs text-muted-foreground sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                ['WS host', config.host || 'MISSING'],
                                ['WS port', String(config.port)],
                                ['Scheme', config.scheme],
                                ['forceTLS', String(config.useTls)],
                                [
                                    'Transports',
                                    config.enabledTransports.join(', '),
                                ],
                                [
                                    'App key',
                                    config.appKey
                                        ? `${config.appKey.slice(0, 8)}…`
                                        : 'MISSING',
                                ],
                                ['Auth endpoint', config.authEndpoint],
                                [
                                    'User channel',
                                    userId
                                        ? `private-${privateUserChannel(userId)}`
                                        : '—',
                                ],
                            ].map(([label, value]) => (
                                <div key={label} className="flex gap-2">
                                    <dt>{label}:</dt>
                                    <dd className="truncate text-foreground">
                                        {value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {t('Ontvangen events')} ({events.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-72 space-y-2 overflow-y-auto rounded-md bg-muted/40 p-2">
                                {events.length === 0 ? (
                                    <p className="mt-10 text-center text-sm text-muted-foreground">
                                        {t('Nog geen events.')}
                                    </p>
                                ) : (
                                    events.map((ev) => (
                                        <div
                                            key={ev.id}
                                            className="rounded-md border bg-card p-2 font-mono text-xs"
                                        >
                                            <div className="mb-1 flex justify-between gap-2 text-muted-foreground">
                                                <span className="truncate text-emerald-600 dark:text-emerald-400">
                                                    {ev.channel} → {ev.event}
                                                </span>
                                                <span>{ev.ts}</span>
                                            </div>
                                            <pre className="break-all whitespace-pre-wrap text-foreground">
                                                {JSON.stringify(
                                                    ev.data,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {t('Debuglog')} ({logs.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-72 space-y-0.5 overflow-y-auto rounded-md bg-muted/40 p-2 font-mono text-xs">
                                {logs.length === 0 ? (
                                    <p className="mt-10 text-center font-sans text-sm text-muted-foreground">
                                        {t('Geen logregels.')}
                                    </p>
                                ) : (
                                    logs.map((log) => (
                                        <div
                                            key={log.id}
                                            className="flex gap-2 leading-5"
                                        >
                                            <span className="shrink-0 text-muted-foreground">
                                                {log.ts}
                                            </span>
                                            <span
                                                className={LOG_COLOR[log.level]}
                                            >
                                                [{log.level.toUpperCase()}]
                                            </span>
                                            <span className="break-all text-foreground">
                                                {log.message}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {apiResult && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {t('Laatste API-antwoord')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre
                                className={cn(
                                    'rounded-md bg-muted/40 p-2 font-mono text-xs break-all whitespace-pre-wrap',
                                    apiResult.ok
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400',
                                )}
                            >
                                {apiResult.body}
                            </pre>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

RealtimeIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Realtime console', href: realtime.index(wayfinderLocale()) },
    ],
};
