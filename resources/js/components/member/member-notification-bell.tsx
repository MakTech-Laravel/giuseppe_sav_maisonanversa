import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
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
import type { NotificationItem } from '@/types/notification';

/** Reads the shared `notifications` prop, which realtime events refresh. */
export function MemberNotificationBell() {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const { notifications: shared } = usePage().props;
    const [open, setOpen] = useState(false);

    const notifications = shared?.preview ?? [];
    const unreadCount = shared?.unreadCount ?? 0;

    const refresh = () => router.reload({ only: ['notifications'] });

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (nextOpen) {
            refresh();
        }
    };

    const handleItemClick = (notification: NotificationItem) => {
        if (notification.read) {
            if (notification.href) {
                window.location.assign(notification.href);
            }

            return;
        }

        router.post(
            markAsRead.url({ locale, notification: notification.id }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    if (notification.href) {
                        window.location.assign(notification.href);
                    }
                },
            },
        );
    };

    const handleMarkAllRead = () => {
        router.post(
            markAllAsRead.url({ locale }),
            {},
            { preserveScroll: true, preserveState: true },
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
                    {unreadCount > 0 ? (
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
                                {!notification.read ? (
                                    <span className="size-1.5 shrink-0 rounded-full bg-gold" />
                                ) : null}
                            </span>
                            {notification.message ? (
                                <span className="font-sans text-[11px] text-sand">
                                    {notification.message}
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
