import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import adminNotifications from '@/routes/admin/notifications';

export function AdminNotificationBell() {
    const { t } = useTranslation();
    const { notifications } = usePage().props;
    const [open, setOpen] = useState(false);
    const locale = wayfinderLocale();

    if (!notifications) {
        return null;
    }

    const { unreadCount, preview } = notifications;

    const markAllRead = () => {
        router.post(
            adminNotifications.readAll(locale).url,
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative size-8"
                    aria-label={t('Meldingen')}
                    data-test="admin-notifications-bell"
                >
                    <Bell className="size-4" />
                    {unreadCount > 0 ? (
                        <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[9px] leading-none font-semibold text-white tabular-nums">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                    ) : null}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="end"
                className="dark w-80 overflow-hidden rounded-lg border-gold/40 bg-choc text-white shadow-xl"
            >
                <div className="flex items-center justify-between border-b border-gold/25 px-3 py-2.5">
                    <p className="text-sm font-semibold text-white">
                        {t('Meldingen')}
                    </p>
                    {unreadCount > 0 ? (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="text-xs text-sand transition-colors hover:text-white"
                        >
                            {t('Alles gelezen')}
                        </button>
                    ) : null}
                </div>
                {preview.length === 0 ? (
                    <p className="px-3 py-8 text-center text-sm text-white/70">
                        {t('Geen meldingen.')}
                    </p>
                ) : (
                    <ul className="max-h-96 divide-y divide-gold/15 overflow-y-auto">
                        {preview.map((item) => (
                            <li key={item.id}>
                                <Link
                                    href={adminNotifications.show({
                                        locale,
                                        userNotification: item.id,
                                    })}
                                    onClick={() => setOpen(false)}
                                    className={cn(
                                        'flex gap-3 px-3 py-2.5 transition-colors hover:bg-choc2',
                                        !item.read && 'bg-gold/15',
                                    )}
                                >
                                    <NotificationTypeIcon type={item.type} />
                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-center justify-between gap-2">
                                            <span className="truncate text-sm font-medium text-white">
                                                {item.title}
                                            </span>
                                            {!item.read ? (
                                                <span className="size-2 shrink-0 rounded-full bg-gold" />
                                            ) : null}
                                        </span>
                                        <span className="line-clamp-2 text-xs text-white/75">
                                            {item.message}
                                        </span>
                                        <span className="text-[10px] text-white/55">
                                            {item.created_at_human}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="border-t border-gold/25 p-2">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="w-full text-white hover:bg-choc2 hover:text-white"
                    >
                        <Link
                            href={adminNotifications.index(locale)}
                            onClick={() => setOpen(false)}
                        >
                            {t('Alle meldingen')}
                        </Link>
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
