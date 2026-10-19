import { Head, router, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    CheckCircle2,
    RefreshCw,
    ServerCog,
    Wifi,
    XCircle,
} from 'lucide-react';
import { useEffect, useState } from 'react';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { callJson } from '@/realtime/call-json';
import serversMaintenance from '@/routes/admin/servers-maintenance';
import { PERMISSIONS } from '@/types/permissions';

type ServiceCard = {
    id: string;
    label: string;
    status: string;
    is_stale: boolean;
    coolify_uuid: string | null;
    coolify_configured: boolean;
    container_status: string | null;
    last_success_at: string | null;
    last_failure_at: string | null;
    last_checked_at: string | null;
    last_restart_at: string | null;
    last_latency_ms: number | null;
    restart_count: number;
    consecutive_failures: number;
    restart_in_progress: boolean;
    auto_recovery_enabled: boolean;
    last_error_category: string | null;
    last_error_message: string | null;
    uptime: {
        availability_percent: number | null;
        healthy_checks: number;
        total_checks: number;
        window_hours: number;
    };
    cpu: number | null;
    memory: number | null;
    public_url?: string;
    health_path?: string;
    hostname?: string;
    port?: number;
    scheme?: string;
    websocket_status?: string;
    recent_checks: Array<{
        status: string;
        http_status: number | null;
        websocket_ok: boolean | null;
        latency_ms: number | null;
        error_message: string | null;
        checked_at: string | null;
    }>;
    incidents: Array<{
        status: string;
        summary: string;
        started_at: string | null;
        resolved_at: string | null;
    }>;
    restarts: Array<{
        trigger: string;
        outcome: string;
        reason: string | null;
        health_verified: boolean | null;
        coolify_deployment_uuid: string | null;
        requested_at: string | null;
        completed_at: string | null;
    }>;
};

type PageProps = {
    combined_status: string;
    realtime_status: string;
    coolify_configured: boolean;
    auto_recovery: {
        global_enabled: boolean;
        cooldown_seconds: number;
    };
    services: {
        'main-app': ServiceCard;
        reverb: ServiceCard;
    };
    auth?: {
        permissions?: string[];
    };
};

const STATUS_STYLES: Record<string, string> = {
    healthy: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    unhealthy: 'bg-red-500/15 text-red-700 dark:text-red-400',
    unreachable: 'bg-orange-500/15 text-orange-700 dark:text-orange-400',
    unknown: 'bg-muted text-muted-foreground',
    degraded: 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
};

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function StatusBadge({ status }: { status: string }) {
    return (
        <Badge
            variant="secondary"
            className={cn('capitalize', STATUS_STYLES[status] ?? STATUS_STYLES.unknown)}
        >
            {status.replaceAll('_', ' ')}
        </Badge>
    );
}

function ServicePanel({
    service,
    canRestart,
    busy,
    actionDisabled = false,
    onRestart,
}: {
    service: ServiceCard;
    canRestart: boolean;
    busy: boolean;
    actionDisabled?: boolean;
    onRestart: () => void;
}) {
    const { t } = useTranslation();
    const spinning = busy || service.restart_in_progress;

    return (
        <Card>
            <CardHeader className="space-y-3">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            {service.id === 'reverb' ? (
                                <Wifi
                                    className={cn(
                                        'h-5 w-5',
                                        spinning && 'animate-spin',
                                    )}
                                />
                            ) : (
                                <ServerCog
                                    className={cn(
                                        'h-5 w-5',
                                        spinning && 'animate-spin',
                                    )}
                                />
                            )}
                            {service.label}
                        </CardTitle>
                        <CardDescription>
                            {service.id === 'main-app'
                                ? service.public_url || t('Public URL not configured')
                                : `${service.scheme}://${service.hostname}:${service.port}`}
                        </CardDescription>
                    </div>
                    <StatusBadge status={service.status} />
                </div>
                {service.is_stale && (
                    <p className="text-sm text-amber-700 dark:text-amber-400">
                        {t('Monitoring data is stale — scheduler may be unavailable.')}
                    </p>
                )}
            </CardHeader>
            <CardContent className="space-y-5">
                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-muted-foreground">{t('Last success')}</dt>
                        <dd>{formatWhen(service.last_success_at)}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">{t('Last failure')}</dt>
                        <dd>{formatWhen(service.last_failure_at)}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">{t('Latency')}</dt>
                        <dd>
                            {service.last_latency_ms !== null
                                ? `${service.last_latency_ms} ms`
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">{t('Observed uptime (24h)')}</dt>
                        <dd>
                            {service.uptime.availability_percent !== null
                                ? `${service.uptime.availability_percent}% (${service.uptime.healthy_checks}/${service.uptime.total_checks})`
                                : t('No observed checks yet')}
                        </dd>
                    </div>
                    {service.container_status && (
                        <div>
                            <dt className="text-muted-foreground">
                                {t('Container status')}
                            </dt>
                            <dd>{service.container_status}</dd>
                        </div>
                    )}
                    <div>
                        <dt className="text-muted-foreground">{t('Restart count')}</dt>
                        <dd>{service.restart_count}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">{t('Coolify resource')}</dt>
                        <dd>
                            {service.coolify_configured
                                ? service.coolify_uuid
                                : t('Not configured')}
                        </dd>
                    </div>
                    {service.cpu !== null && service.memory !== null && (
                        <div>
                            <dt className="text-muted-foreground">
                                {t('CPU / Memory')}
                            </dt>
                            <dd>
                                {service.cpu}% / {service.memory}
                            </dd>
                        </div>
                    )}
                    {service.id === 'reverb' && (
                        <div className="sm:col-span-2">
                            <dt className="text-muted-foreground">
                                {t('WebSocket handshake')}
                            </dt>
                            <dd>{service.websocket_status ?? service.status}</dd>
                        </div>
                    )}
                    {service.last_error_message && (
                        <div className="sm:col-span-2">
                            <dt className="text-muted-foreground">{t('Last error')}</dt>
                            <dd className="text-red-600 dark:text-red-400">
                                {service.last_error_message}
                            </dd>
                        </div>
                    )}
                </dl>

                {canRestart && (
                    <Button
                        variant="outline"
                        disabled={
                            actionDisabled ||
                            spinning ||
                            !service.coolify_configured
                        }
                        onClick={onRestart}
                    >
                        <RefreshCw
                            className={cn(
                                'mr-2 h-4 w-4',
                                spinning && 'animate-spin',
                            )}
                        />
                        {spinning
                            ? t('Working…')
                            : service.id === 'main-app'
                              ? t('Restart Main Application')
                              : t('Restart Reverb')}
                    </Button>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <div>
                        <h3 className="mb-2 text-sm font-medium">
                            {t('Recent incidents')} ({t('latest 3')})
                        </h3>
                        <ul className="space-y-2 text-sm">
                            {service.incidents.length === 0 && (
                                <li className="text-muted-foreground">{t('None')}</li>
                            )}
                            {service.incidents.map((incident, index) => (
                                <li
                                    key={`${incident.started_at}-${index}`}
                                    className="rounded-md border px-3 py-2"
                                >
                                    <div className="font-medium">{incident.summary}</div>
                                    <div className="text-muted-foreground">
                                        {formatWhen(incident.started_at)}
                                        {incident.resolved_at
                                            ? ` → ${formatWhen(incident.resolved_at)}`
                                            : ''}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div>
                        <h3 className="mb-2 text-sm font-medium">
                            {t('Restart / deploy history')} ({t('latest 3')})
                        </h3>
                        <ul className="space-y-2 text-sm">
                            {service.restarts.length === 0 && (
                                <li className="text-muted-foreground">{t('None')}</li>
                            )}
                            {service.restarts.map((restart, index) => (
                                <li
                                    key={`${restart.requested_at}-${index}`}
                                    className="rounded-md border px-3 py-2"
                                >
                                    <div className="font-medium">
                                        {restart.trigger} · {restart.outcome}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {formatWhen(restart.requested_at)}
                                        {restart.coolify_deployment_uuid
                                            ? ` · ${restart.coolify_deployment_uuid}`
                                            : ''}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ServersMaintenanceIndex(props: PageProps) {
    const { t } = useTranslation();
    const page = usePage();
    const locale = wayfinderLocale(page.props);
    const { can } = usePermission();
    const canRestart = can(PERMISSIONS.SERVERS_MAINTENANCE.RESTART);

    const [busyService, setBusyService] = useState<string | null>(null);
    const [checking, setChecking] = useState(false);
    const [savingSettings, setSavingSettings] = useState(false);
    const [actionMessage, setActionMessage] = useState<string | null>(null);
    const [globalEnabled, setGlobalEnabled] = useState(props.auto_recovery.global_enabled);
    const [reverbEnabled, setReverbEnabled] = useState(
        props.services.reverb.auto_recovery_enabled,
    );

    useEffect(() => {
        setGlobalEnabled(props.auto_recovery.global_enabled);
        setReverbEnabled(props.services.reverb.auto_recovery_enabled);
    }, [
        props.auto_recovery.global_enabled,
        props.services.reverb.auto_recovery_enabled,
    ]);

    const pageBusy = checking || busyService !== null || savingSettings;

    function reloadPage(): Promise<void> {
        return new Promise((resolve) => {
            router.reload({
                onFinish: () => resolve(),
                onCancel: () => resolve(),
                onError: () => resolve(),
            });
        });
    }

    const combinedIcon =
        props.combined_status === 'both_healthy' ? (
            <CheckCircle2 className="h-5 w-5 text-emerald-600" />
        ) : props.combined_status === 'monitoring_unavailable' ? (
            <AlertTriangle className="h-5 w-5 text-amber-600" />
        ) : (
            <XCircle className="h-5 w-5 text-red-600" />
        );

    async function restartReverb() {
        if (pageBusy) {
            return;
        }

        if (
            !window.confirm(
                t('Restart Reverb only? The main application will keep serving HTTP.'),
            )
        ) {
            return;
        }

        setBusyService('reverb');
        setActionMessage(null);

        try {
            const result = await callJson(
                serversMaintenance.restart.url({ locale, service: 'reverb' }),
                'post',
            );
            const message =
                typeof result.json.message === 'string'
                    ? result.json.message
                    : `reverb: ${String(result.json.data ?? result.status)}`;
            setActionMessage(message);
            await reloadPage();
        } catch {
            setActionMessage(t('Restart request failed.'));
        } finally {
            setBusyService(null);
        }
    }

    function runChecksNow() {
        if (pageBusy) {
            return;
        }

        setChecking(true);
        setActionMessage(null);

        router.post(
            serversMaintenance.runChecks.url({ locale }),
            {},
            {
                preserveScroll: true,
                onFinish: () => setChecking(false),
            },
        );
    }

    function saveRecoverySettings() {
        if (!canRestart || pageBusy) {
            setActionMessage(
                t('You need permission to change auto-recovery settings.'),
            );

            return;
        }

        setSavingSettings(true);
        setActionMessage(null);

        // Send 0/1 — Inertia FormData can drop boolean `false`, which fails `required|boolean`.
        router.post(
            serversMaintenance.settings.url({ locale }),
            {
                global_enabled: globalEnabled ? 1 : 0,
                reverb_enabled: reverbEnabled ? 1 : 0,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setActionMessage(t('Recovery settings updated.'));
                },
                onError: (errors) => {
                    const first = Object.values(errors)[0];
                    setActionMessage(
                        typeof first === 'string'
                            ? first
                            : t('Could not save recovery settings.'),
                    );
                },
                onFinish: () => setSavingSettings(false),
            },
        );
    }

    return (
        <>
            <Head title={t('Reverb Server')} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <AdminPageHeader
                    title={t('Reverb Server')}
                    description={t(
                        'Health, uptime, and restart controls for Laravel Reverb.',
                    )}
                    icon={Activity}
                >
                    <Button
                        variant="outline"
                        disabled={pageBusy}
                        onClick={runChecksNow}
                    >
                        <RefreshCw
                            className={cn(
                                'mr-2 h-4 w-4',
                                checking && 'animate-spin',
                            )}
                        />
                        {checking ? t('Checking…') : t('Run checks now')}
                    </Button>
                </AdminPageHeader>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {combinedIcon}
                            {t('Overall status')}
                        </CardTitle>
                        <CardDescription>
                            {t('Combined summary does not replace per-service status.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-3">
                        <StatusBadge status={props.combined_status} />
                        <Badge variant="outline">
                            {t('Realtime')}: {props.realtime_status}
                        </Badge>
                        <Badge variant="outline">
                            Coolify:{' '}
                            {props.coolify_configured
                                ? t('Configured')
                                : t('Not configured')}
                        </Badge>
                        {actionMessage && (
                            <p className="w-full text-sm text-muted-foreground">
                                {actionMessage}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-1">
                    <ServicePanel
                        service={props.services.reverb}
                        canRestart={canRestart}
                        busy={busyService === 'reverb'}
                        actionDisabled={pageBusy}
                        onRestart={() => void restartReverb()}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Auto restart')}</CardTitle>
                        <CardDescription>
                            {t(
                                'When enabled: if Reverb is unhealthy or stopped, restart it via Coolify. Skips if a restart is already running.',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {!canRestart && (
                            <p className="text-sm text-amber-700 dark:text-amber-400">
                                {t(
                                    'You need the servers-maintenance.restart permission to change these settings. Re-seed roles/permissions if this is a new install.',
                                )}
                            </p>
                        )}
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="global-recovery"
                                checked={globalEnabled}
                                disabled={!canRestart || pageBusy}
                                onCheckedChange={(checked) =>
                                    setGlobalEnabled(checked === true)
                                }
                            />
                            <Label htmlFor="global-recovery">
                                {t('Enable auto restart')}
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="reverb-recovery"
                                checked={reverbEnabled}
                                disabled={!canRestart || pageBusy}
                                onCheckedChange={(checked) =>
                                    setReverbEnabled(checked === true)
                                }
                            />
                            <Label htmlFor="reverb-recovery">
                                {t('Apply to Reverb')}
                            </Label>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {t('Restart cooldown')}:{' '}
                            {props.auto_recovery.cooldown_seconds}s
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                disabled={!canRestart || pageBusy}
                                onClick={saveRecoverySettings}
                            >
                                <RefreshCw
                                    className={cn(
                                        'mr-2 h-4 w-4',
                                        savingSettings && 'animate-spin',
                                    )}
                                />
                                {savingSettings
                                    ? t('Saving…')
                                    : t('Save auto restart settings')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
