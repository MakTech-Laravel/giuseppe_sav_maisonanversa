import { Head, Link, router } from '@inertiajs/react';
import { Bell, CheckCheck, ExternalLink, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DataPagination } from '@/components/admin/data-pagination';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { NOTIFICATION_RECEIVED_WINDOW_EVENT } from '@/realtime/types';
import { dashboard } from '@/routes/admin';
import adminNotifications from '@/routes/admin/notifications';
import type { Paginated } from '@/types/admin';
import type {
    NotificationItem,
    NotificationStatusFilter,
} from '@/types/notification';

interface NotificationFilters {
    status: NotificationStatusFilter;
    type: string;
    search: string;
}

interface NotificationsIndexProps {
    rows: Paginated<NotificationItem>;
    filters: NotificationFilters;
    types: { value: string; label: string }[];
    unreadCount: number;
}

const STATUS_TABS: { value: NotificationStatusFilter; label: string }[] = [
    { value: 'all', label: 'Alle' },
    { value: 'unread', label: 'Ongelezen' },
    { value: 'read', label: 'Gelezen' },
];

export default function NotificationsIndex({
    rows: paginated,
    filters,
    types,
    unreadCount,
}: NotificationsIndexProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState<NotificationStatusFilter>(
        filters.status ?? 'all',
    );
    const [type, setType] = useState(filters.type || 'all');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                adminNotifications.index(locale).url,
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    type: type === 'all' ? undefined : type,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, type, locale]);

    useEffect(() => {
        const refresh = () => router.reload({ only: ['rows', 'unreadCount'] });

        window.addEventListener(NOTIFICATION_RECEIVED_WINDOW_EVENT, refresh);

        return () =>
            window.removeEventListener(
                NOTIFICATION_RECEIVED_WINDOW_EVENT,
                refresh,
            );
    }, []);

    const markAllRead = () => {
        router.post(
            adminNotifications.readAll(locale).url,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={t('Meldingen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Meldingen')}
                    description={t(
                        'Uw persoonlijke meldingen en systeemberichten.',
                    )}
                    icon={Bell}
                >
                    <Button
                        variant="outline"
                        onClick={markAllRead}
                        disabled={unreadCount === 0}
                    >
                        <CheckCheck className="h-4 w-4" />
                        {t('Alles gelezen')}
                    </Button>
                </AdminPageHeader>

                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="inline-flex rounded-lg border bg-muted/40 p-1">
                        {STATUS_TABS.map((tab) => (
                            <button
                                key={tab.value}
                                type="button"
                                onClick={() => setStatus(tab.value)}
                                className={cn(
                                    'rounded-md px-3 py-1.5 text-sm transition-colors',
                                    status === tab.value
                                        ? 'bg-background font-medium shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {t(tab.label)}
                                {tab.value === 'unread' && unreadCount > 0 ? (
                                    <Badge
                                        variant="secondary"
                                        className="ml-1.5 h-5 px-1.5 tabular-nums"
                                    >
                                        {unreadCount}
                                    </Badge>
                                ) : null}
                            </button>
                        ))}
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Select value={type} onValueChange={setType}>
                            <SelectTrigger className="sm:w-44">
                                <SelectValue placeholder={t('Type')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('Alle types')}
                                </SelectItem>
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
                        <div className="relative w-full sm:w-64">
                            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={t('Zoek meldingen…')}
                                className="pl-9"
                            />
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <ul className="divide-y">
                        {paginated.data.map((item) => (
                            <li
                                key={item.id}
                                className={cn(
                                    'flex items-start gap-4 px-4 py-4',
                                    !item.read && 'bg-primary/5',
                                )}
                            >
                                <NotificationTypeIcon
                                    type={item.type}
                                    className="mt-0.5"
                                />
                                <Link
                                    href={adminNotifications.show({
                                        locale,
                                        userNotification: item.id,
                                    })}
                                    className="min-w-0 flex-1"
                                >
                                    <span className="flex flex-wrap items-center gap-2">
                                        <span
                                            className={cn(
                                                'text-sm',
                                                item.read
                                                    ? 'font-medium'
                                                    : 'font-semibold',
                                            )}
                                        >
                                            {item.title}
                                        </span>
                                        {item.type_label ? (
                                            <Badge
                                                variant="outline"
                                                className="text-[10px]"
                                            >
                                                {item.type_label}
                                            </Badge>
                                        ) : null}
                                        {!item.read ? (
                                            <span className="size-2 rounded-full bg-primary" />
                                        ) : null}
                                    </span>
                                    <span className="mt-1 line-clamp-2 block text-sm text-muted-foreground">
                                        {item.message}
                                    </span>
                                    <span className="mt-1 block text-xs text-muted-foreground/80">
                                        {item.created_at_human}
                                    </span>
                                </Link>
                                <div className="flex shrink-0 items-center gap-1">
                                    {item.href ? (
                                        <Button
                                            asChild
                                            variant="ghost"
                                            size="icon"
                                        >
                                            <a
                                                href={item.href}
                                                title={
                                                    item.link_label ??
                                                    t('Openen')
                                                }
                                            >
                                                <ExternalLink className="h-4 w-4" />
                                            </a>
                                        </Button>
                                    ) : null}
                                    {!item.read ? (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title={t('Markeer als gelezen')}
                                            onClick={() =>
                                                router.post(
                                                    adminNotifications.read({
                                                        locale,
                                                        userNotification:
                                                            item.id,
                                                    }).url,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <CheckCheck className="h-4 w-4" />
                                        </Button>
                                    ) : null}
                                    <ConfirmDeleteDialog
                                        description={t(
                                            'Deze melding uit uw inbox verwijderen?',
                                        )}
                                        onConfirm={() =>
                                            router.delete(
                                                adminNotifications.destroy({
                                                    locale,
                                                    userNotification: item.id,
                                                }).url,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="text-muted-foreground hover:text-destructive"
                                            title={t('Verwijderen')}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </ConfirmDeleteDialog>
                                </div>
                            </li>
                        ))}
                    </ul>
                    {paginated.data.length === 0 && (
                        <div className="px-4 py-16 text-center text-sm text-muted-foreground">
                            {t('Geen meldingen gevonden.')}
                        </div>
                    )}
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Meldingen',
            href: adminNotifications.index(wayfinderLocale()),
        },
    ],
};
