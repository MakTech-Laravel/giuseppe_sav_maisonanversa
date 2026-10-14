import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import adminNotifications from '@/routes/admin/notifications';
import memberNotifications from '@/routes/member/notifications';
import type { NotificationItem } from '@/types/notification';

/**
 * Reads the shared `notifications` prop, which realtime events refresh.
 * Renders nothing for guests. Admins are routed to the admin inbox because
 * the member routes sit behind the `customer` middleware.
 */
export function MemberNotificationBell({ className }: { className?: string }) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const { auth, notifications: shared } = usePage().props;
    const [open, setOpen] = useState(false);

    const user = auth?.user;

    if (!user) {
        return null;
    }

    const isAdmin = Boolean(user.is_admin);
    const notifications = shared?.preview ?? [];
    const unreadCount = shared?.unreadCount ?? 0;

    const indexHref = isAdmin
        ? adminNotifications.index(locale)
        : memberNotifications.index(locale);
    const readAllUrl = isAdmin
        ? adminNotifications.readAll(locale).url
        : memberNotifications.readAll(locale).url;
    const showUrl = (id: number) =>
        isAdmin
            ? adminNotifications.show({ locale, userNotification: id }).url
            : memberNotifications.show({ locale, userNotification: id }).url;

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (nextOpen) {
            router.reload({ only: ['notifications'] });
        }
    };

    const handleItemClick = (notification: NotificationItem) => {
        router.visit(showUrl(notification.id));
    };

    const handleMarkAllRead = () => {
        router.post(
            readAllUrl,
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
                    className={cn(
                        'relative flex size-8 shrink-0 items-center justify-center border border-gold/25 bg-choc3 text-cream transition-colors hover:border-gold/40 hover:bg-choc2',
                        className,
                    )}
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
                className="z-[260] w-80 max-w-[calc(100vw-1.5rem)] border-gold/25 bg-choc3 text-cream"
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
                    <div className="max-h-96 overflow-y-auto">
                        {notifications.map((notification) => (
                            <DropdownMenuItem
                                key={notification.id}
                                onSelect={() => handleItemClick(notification)}
                                className={cn(
                                    'flex cursor-pointer flex-col items-start gap-0.5 rounded-none py-2 whitespace-normal text-cream focus:bg-choc2 focus:text-cream',
                                    !notification.read && 'bg-gold/10',
                                )}
                            >
                                <span className="flex w-full items-center justify-between gap-2 font-sans text-[11px] font-medium tracking-[0.04em]">
                                    {notification.title}
                                    {!notification.read ? (
                                        <span className="size-1.5 shrink-0 rounded-full bg-gold" />
                                    ) : null}
                                </span>
                                {notification.message ? (
                                    <span className="line-clamp-2 font-sans text-[11px] text-sand">
                                        {notification.message}
                                    </span>
                                ) : null}
                                <span className="font-sans text-[9px] tracking-[0.1em] text-sand/70 uppercase">
                                    {notification.created_at_human}
                                </span>
                            </DropdownMenuItem>
                        ))}
                    </div>
                )}
                <DropdownMenuSeparator className="bg-gold/20" />
                <DropdownMenuItem
                    asChild
                    className="justify-center rounded-none py-2 font-sans text-[10px] tracking-[0.18em] text-gold uppercase focus:bg-choc2 focus:text-cream"
                >
                    <Link href={indexHref}>{t('Alle meldingen')}</Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
