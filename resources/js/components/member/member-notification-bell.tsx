import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import {
    markAllAsRead,
    markAsRead,
} from '@/actions/App/Http/Controllers/Member/NotificationController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

type SharedNotification = {
    id: string;
    type: string;
    data: {
        title?: string;
        body?: string;
    };
    read_at: string | null;
    created_at: string | null;
};

export function MemberNotificationBell() {
    const { t } = useTranslation();
    const { notifications } = usePage().props;
    const unreadCount = notifications?.unread_count ?? 0;
    const recent = (notifications?.recent ?? []) as SharedNotification[];
    const locale = wayfinderLocale();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                type="button"
                className="relative inline-flex size-8 items-center justify-center border border-gold/30 text-cream transition-colors hover:border-gold hover:text-gold"
                aria-label={t('Meldingen')}
            >
                <Bell className="size-3.5" aria-hidden />
                {unreadCount > 0 ? (
                    <span className="absolute -top-1 -right-1 flex min-w-4 items-center justify-center bg-gold px-1 font-sans text-[8px] leading-4 text-choc">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                ) : null}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="w-80 rounded-none border-gold/30 bg-choc2 text-cream"
            >
                <DropdownMenuLabel className="flex items-center justify-between font-sans text-[10px] tracking-[0.16em] text-gold uppercase">
                    <span>{t('Meldingen')}</span>
                    {unreadCount > 0 ? (
                        <button
                            type="button"
                            className="font-sans text-[9px] tracking-[0.12em] text-sand uppercase hover:text-gold"
                            onClick={() =>
                                router.post(markAllAsRead.url(locale), {}, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            {t('Alles gelezen')}
                        </button>
                    ) : null}
                </DropdownMenuLabel>
                <DropdownMenuSeparator className="bg-gold/20" />
                {recent.length === 0 ? (
                    <p className="px-2 py-4 font-sans text-[12px] text-sand">
                        {t('Geen meldingen.')}
                    </p>
                ) : (
                    recent.map((item) => (
                        <DropdownMenuItem
                            key={item.id}
                            className="cursor-pointer rounded-none focus:bg-choc3 focus:text-cream"
                            onSelect={() => {
                                if (!item.read_at) {
                                    router.post(
                                        markAsRead.url({
                                            locale,
                                            notification: item.id,
                                        }),
                                        {},
                                        { preserveScroll: true },
                                    );
                                }
                            }}
                        >
                            <div className="flex flex-col gap-1 py-1">
                                <p
                                    className={`font-sans text-[11px] tracking-[0.08em] uppercase ${
                                        item.read_at
                                            ? 'text-sand'
                                            : 'text-gold'
                                    }`}
                                >
                                    {item.data.title ?? item.type}
                                </p>
                                {item.data.body ? (
                                    <p className="line-clamp-2 font-serif text-[13px] leading-snug text-cream/90">
                                        {item.data.body}
                                    </p>
                                ) : null}
                            </div>
                        </DropdownMenuItem>
                    ))
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
