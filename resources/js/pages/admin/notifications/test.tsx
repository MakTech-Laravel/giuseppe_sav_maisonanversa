import { Head, Link, usePage } from '@inertiajs/react';
import {
    BellRing,
    Eraser,
    Eye,
    Inbox,
    RefreshCw,
    Send,
    ShieldCheck,
    Zap,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { showNotificationToast } from '@/components/notifications/notification-toast';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useEchoConnection } from '@/hooks/use-echo-connection';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { callJson } from '@/realtime/call-json';
import {
    CONNECTION_STATUS_LABELS,
    NOTIFICATION_RECEIVED_WINDOW_EVENT,
    privateUserChannel,
} from '@/realtime/types';
import type { ConnectionStatus } from '@/realtime/types';
import { dashboard } from '@/routes/admin';
import adminNotifications from '@/routes/admin/notifications';
import notificationTest from '@/routes/admin/notifications/test';
import type { NotificationItem, NotificationType } from '@/types/notification';

type Target = 'self' | 'admins' | 'user';
type Mode = 'sync' | 'queue';

type FormState = {
    target: Target;
    email: string;
    type: NotificationType;
    title: string;
    message: string;
    link_url: string;
    link_label: string;
    mode: Mode;
};

type Received = { id: number; ts: string; payload: NotificationItem };

type ApiResult = { ok: boolean; body: string };

interface NotificationTestProps {
    types: { value: NotificationType; label: string }[];
    queueConnection: string;
    broadcastConnection: string;
}

const STATUS_DOT: Record<ConnectionStatus, string> = {
    connected: 'bg-emerald-500',
    connecting: 'bg-amber-500 animate-pulse',
    unavailable: 'bg-orange-500',
    disconnected: 'bg-orange-500',
    failed: 'bg-red-500',
    idle: 'bg-muted-foreground/50',
    disabled: 'bg-muted-foreground/50',
};

function clock(): string {
    return new Date().toLocaleTimeString('en-GB', { hour12: false });
}

function initialForm(): FormState {
    return {
        target: 'self',
        email: '',
        type: 'info',
        title: 'Testmelding',
        message: 'Dit is een realtime testmelding van Maison Anversa.',
        link_url: '',
        link_label: '',
        mode: 'sync',
    };
}

export default function NotificationTest({
    types,
    queueConnection,
    broadcastConnection,
}: NotificationTestProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const userId = auth?.user?.id;
    const locale = wayfinderLocale();
    const { status, socketId, enabled, reconnect } = useEchoConnection();

    const [form, setForm] = useState<FormState>(initialForm);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    const [received, setReceived] = useState<Received[]>([]);
    const [apiResult, setApiResult] = useState<ApiResult | null>(null);
    const nextId = useRef(0);

    useEffect(() => {
        const onReceived = (event: Event) => {
            const payload = (event as CustomEvent<NotificationItem>).detail;

            setReceived((prev) =>
                [{ id: nextId.current++, ts: clock(), payload }, ...prev].slice(
                    0,
                    50,
                ),
            );
        };

        window.addEventListener(NOTIFICATION_RECEIVED_WINDOW_EVENT, onReceived);

        return () =>
            window.removeEventListener(
                NOTIFICATION_RECEIVED_WINDOW_EVENT,
                onReceived,
            );
    }, []);

    const update = <K extends keyof FormState>(key: K, value: FormState[K]) =>
        setForm((prev) => ({ ...prev, [key]: value }));

    const send = useCallback(
        async (payload: FormState) => {
            setBusy(true);
            setErrors({});

            try {
                const {
                    ok,
                    status: code,
                    json,
                } = await callJson(notificationTest.send.url(locale), 'post', {
                    ...payload,
                    email: payload.target === 'user' ? payload.email : null,
                    link_url: payload.link_url || null,
                    link_label: payload.link_label || null,
                });

                setApiResult({ ok, body: JSON.stringify(json, null, 2) });

                if (code === 422 && json.errors) {
                    const fieldErrors: Record<string, string> = {};

                    Object.entries(
                        json.errors as Record<string, string[]>,
                    ).forEach(([field, messages]) => {
                        fieldErrors[field] = messages[0] ?? '';
                    });
                    setErrors(fieldErrors);
                }
            } catch (err) {
                setApiResult({
                    ok: false,
                    body: err instanceof Error ? err.message : String(err),
                });
            } finally {
                setBusy(false);
            }
        },
        [locale],
    );

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        void send(form);
    };

    const quickSelfTest = () =>
        void send({
            ...initialForm(),
            type: 'success',
            title: `Snelle test ${clock()}`,
            message: t('Als u deze toast ziet, werkt realtime.'),
        });

    const previewToast = () =>
        showNotificationToast(
            {
                id: Date.now(),
                type: form.type,
                type_label:
                    types.find((option) => option.value === form.type)?.label ??
                    null,
                title: form.title || t('Testmelding'),
                message: form.message,
                href: form.link_url || null,
                link_label: form.link_label || null,
                read: false,
                read_at: null,
                created_at: new Date().toISOString(),
                created_at_human: '',
            },
            t,
        );

    const connected = status === 'connected';

    return (
        <>
            <Head title={t('Meldingen testen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Meldingen testen')}
                    description={t(
                        'Verstuur een echte melding en controleer database, Reverb, toast en bel in één keer.',
                    )}
                    icon={BellRing}
                >
                    <Button asChild variant="outline">
                        <Link href={adminNotifications.index(locale)}>
                            <Inbox className="h-4 w-4" />
                            {t('Meldingen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

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
                            {socketId ? (
                                <Badge
                                    variant="secondary"
                                    className="font-mono"
                                >
                                    socket_id: {socketId}
                                </Badge>
                            ) : null}
                            {userId ? (
                                <Badge variant="outline" className="gap-1">
                                    <ShieldCheck className="h-3 w-3" />
                                    private-{privateUserChannel(userId)}
                                </Badge>
                            ) : null}
                        </CardTitle>
                        <CardDescription>
                            {t(
                                'Direct: de broadcast gebeurt in het verzoek zelf. Queue: een queue worker (php artisan queue:work) moet draaien.',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-2">
                        <Button
                            size="sm"
                            disabled={busy || !userId}
                            onClick={quickSelfTest}
                        >
                            <Zap className="h-4 w-4" />
                            {t('Snelle test naar mij')}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={previewToast}
                        >
                            <Eye className="h-4 w-4" />
                            {t('Voorbeeld-toast')}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={!enabled}
                            onClick={reconnect}
                        >
                            <RefreshCw className="h-4 w-4" />
                            {t('Opnieuw verbinden')}
                        </Button>
                        <span className="font-mono text-xs text-muted-foreground">
                            broadcast: {broadcastConnection} · queue:{' '}
                            {queueConnection}
                        </span>
                        {!connected ? (
                            <span className="text-xs text-amber-600 dark:text-amber-400">
                                {t(
                                    'Niet verbonden: de melding wordt opgeslagen, maar er komt geen live toast.',
                                )}
                            </span>
                        ) : null}
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {t('Testmelding opstellen')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={onSubmit}>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <Label>{t('Ontvanger')}</Label>
                                        <Select
                                            value={form.target}
                                            onValueChange={(value) =>
                                                update(
                                                    'target',
                                                    value as Target,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="self">
                                                    {t('Mijzelf')}
                                                </SelectItem>
                                                <SelectItem value="admins">
                                                    {t('Alle beheerders')}
                                                </SelectItem>
                                                <SelectItem value="user">
                                                    {t('Gebruiker (e-mail)')}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>{t('Modus')}</Label>
                                        <Select
                                            value={form.mode}
                                            onValueChange={(value) =>
                                                update('mode', value as Mode)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="sync">
                                                    {t('Direct (zonder queue)')}
                                                </SelectItem>
                                                <SelectItem value="queue">
                                                    {t('Via queue')}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {form.target === 'user' ? (
                                    <Field
                                        label={t('E-mailadres')}
                                        error={errors.email}
                                    >
                                        <Input
                                            type="email"
                                            value={form.email}
                                            onChange={(event) =>
                                                update(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="lid@example.com"
                                        />
                                    </Field>
                                ) : null}

                                <Field label={t('Type')} error={errors.type}>
                                    <Select
                                        value={form.type}
                                        onValueChange={(value) =>
                                            update(
                                                'type',
                                                value as NotificationType,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <Field label={t('Titel')} error={errors.title}>
                                    <Input
                                        value={form.title}
                                        maxLength={120}
                                        onChange={(event) =>
                                            update('title', event.target.value)
                                        }
                                    />
                                </Field>

                                <Field
                                    label={t('Bericht')}
                                    error={errors.message}
                                >
                                    <Textarea
                                        rows={3}
                                        value={form.message}
                                        maxLength={500}
                                        onChange={(event) =>
                                            update(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t('Link (optioneel)')}
                                        error={errors.link_url}
                                    >
                                        <Input
                                            value={form.link_url}
                                            placeholder="https://"
                                            onChange={(event) =>
                                                update(
                                                    'link_url',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t('Linktekst')}
                                        error={errors.link_label}
                                    >
                                        <Input
                                            value={form.link_label}
                                            maxLength={60}
                                            onChange={(event) =>
                                                update(
                                                    'link_label',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button type="submit" disabled={busy}>
                                        <Send className="h-4 w-4" />
                                        {t('Melding versturen')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => {
                                            setForm(initialForm());
                                            setErrors({});
                                        }}
                                    >
                                        {t('Herstellen')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">
                                {t('Ontvangen realtime meldingen')} (
                                {received.length})
                            </CardTitle>
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => {
                                    setReceived([]);
                                    setApiResult(null);
                                }}
                            >
                                <Eraser className="h-4 w-4" />
                                {t('Wissen')}
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[26rem] space-y-2 overflow-y-auto rounded-md bg-muted/40 p-2">
                                {received.length === 0 ? (
                                    <p className="mt-16 text-center text-sm text-muted-foreground">
                                        {t(
                                            'Nog niets ontvangen. Verstuur een melding naar uzelf.',
                                        )}
                                    </p>
                                ) : (
                                    received.map((entry) => (
                                        <div
                                            key={entry.id}
                                            className="rounded-md border bg-card p-2"
                                        >
                                            <div className="flex items-start gap-2">
                                                <NotificationTypeIcon
                                                    type={entry.payload.type}
                                                    className="size-7"
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex justify-between gap-2 text-sm font-medium">
                                                        <span className="truncate">
                                                            {
                                                                entry.payload
                                                                    .title
                                                            }
                                                        </span>
                                                        <span className="shrink-0 font-mono text-xs text-muted-foreground">
                                                            {entry.ts}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        {entry.payload.message}
                                                    </p>
                                                </div>
                                            </div>
                                            <pre className="mt-2 font-mono text-[11px] break-all whitespace-pre-wrap text-muted-foreground">
                                                {JSON.stringify(
                                                    entry.payload,
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
                </div>

                {apiResult ? (
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
                ) : null}
            </div>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            {error ? <p className="text-xs text-destructive">{error}</p> : null}
        </div>
    );
}

NotificationTest.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Meldingen testen',
            href: notificationTest.index(wayfinderLocale()),
        },
    ],
};
