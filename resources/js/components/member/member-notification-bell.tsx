import { router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    markAllAsRead,
    markAsRead,
} from '@/actions/App/Http/Controllers/Member/NotificationController';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

type MemberNotification = {
    id: string;
    title: string | null;
    body: string | null;
    read_at: string | null;
    created_at: string | null;
};

type NotificationsResponse = {
    data: MemberNotification[];
    unread_count: number;
};

async function fetchNotifications(
    locale: string,
): Promise<NotificationsResponse | null> {
    const response = await fetch(`/${locale}/member/notifications`, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        return null;
    }

    return (await response.json()) as NotificationsResponse;
}

/** Polling keeps the unread badge fresh without a websocket connection. */
const POLL_INTERVAL_MS = 60_000;

export function MemberNotificationBell() {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [notifications, setNotifications] = useState<MemberNotification[]>(
        [],
    );
    const [unreadCount, setUnreadCount] = useState(0);
    const [loaded, setLoaded] = useState(false);

    const refresh = useCallback(() => {
        fetchNotifications(locale).then((payload) => {
            if (!payload) {
                return;
            }

            setNotifications(payload.data);
            setUnreadCount(payload.unread_count);
            setLoaded(true);
        });
    }, [locale]);

    useEffect(() => {
        refresh();
        const interval = window.setInterval(refresh, POLL_INTERVAL_MS);

        return () => window.clearInterval(interval);
    }, [refresh]);

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (nextOpen) {
            refresh();
        }
    };

    const handleItemClick = (notification: MemberNotification) => {
        if (notification.read_at) {
            return;
        }

        router.post(
            markAsRead.url({ locale, notification: notification.id }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: refresh,
            },
        );
    };

    const handleMarkAllRead = () => {
        router.post(
            markAllAsRead.url({ locale }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: refresh,
            },
        );
    };

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    aria-label={t('Meldingen')}
                    className="relative flex size-8 shrink-0 items-center justify-center border border-gold/25 bg-choc3 text-cream transition-colors hover:border-gold/40 hover:bg-choc2"
                >
                    <Bell className="size-4" />
                    {loaded && unreadCount > 0 ? (
                        <Badge
                            variant="destructive"
                            className="absolute -top-1.5 -right-1.5 h-4 min-w-4 justify-center rounded-full border-none px-1 text-[9px] leading-none"
                        >
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </Badge>
                    ) : null}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="w-80 border-gold/25 bg-choc3 text-cream"
            >
                <div className="flex items-center justify-between px-2 py-1.5">
                    <DropdownMenuLabel className="p-0 font-sans text-[10px] tracking-[0.18em] text-sand uppercase">
                        {t('Meldingen')}
                    </DropdownMenuLabel>
                    {unreadCount > 0 ? (
                        <button
                            type="button"
                            onClick={handleMarkAllRead}
                            className="font-sans text-[9px] tracking-[0.14em] text-gold uppercase hover:text-cream"
                        >
                            {t('Alles gelezen')}
                        </button>
                    ) : null}
                </div>
                <DropdownMenuSeparator className="bg-gold/20" />
                {notifications.length === 0 ? (
                    <p className="px-2 py-4 text-center font-sans text-[11px] text-sand">
                        {t('Geen meldingen.')}
                    </p>
                ) : (
                    notifications.map((notification) => (
                        <DropdownMenuItem
                            key={notification.id}
                            onSelect={() => handleItemClick(notification)}
                            className="flex cursor-pointer flex-col items-start gap-0.5 rounded-none py-2 whitespace-normal text-cream focus:bg-choc2 focus:text-cream"
                        >
                            <span className="flex w-full items-center justify-between gap-2 font-sans text-[11px] font-medium tracking-[0.04em]">
                                {notification.title}
                                {!notification.read_at ? (
                                    <span className="size-1.5 shrink-0 rounded-full bg-gold" />
                                ) : null}
                            </span>
                            {notification.body ? (
                                <span className="font-sans text-[11px] text-sand">
                                    {notification.body}
                                </span>
                            ) : null}
                            {notification.created_at ? (
                                <span className="font-sans text-[9px] tracking-[0.1em] text-sand/70 uppercase">
                                    {new Date(
                                        notification.created_at,
                                    ).toLocaleString()}
                                </span>
                            ) : null}
                        </DropdownMenuItem>
                    ))
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
