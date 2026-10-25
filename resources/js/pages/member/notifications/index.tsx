import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import {
    MemberEmptyState,
    MemberPageHeader,
    MemberPanel,
} from '@/components/member/member-ui';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { NOTIFICATION_RECEIVED_WINDOW_EVENT } from '@/realtime/types';
import memberNotifications from '@/routes/member/notifications';
import type { Paginated } from '@/types/admin';
import type {
    NotificationItem,
    NotificationStatusFilter,
} from '@/types/notification';

interface MemberNotificationsIndexProps {
    rows: Paginated<NotificationItem>;
    filters: { status: NotificationStatusFilter };
    unreadCount: number;
}

const STATUS_TABS: { value: NotificationStatusFilter; labelKey: string }[] = [
    { value: 'all', labelKey: 'Alle' },
    { value: 'unread', labelKey: 'Ongelezen' },
    { value: 'read', labelKey: 'Gelezen' },
];

const pagerClassName =
    'font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline transition-colors hover:text-cream';

export default function MemberNotificationsIndex({
    rows,
    filters,
    unreadCount,
}: MemberNotificationsIndexProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const status = filters.status ?? 'all';

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
            memberNotifications.readAll(locale).url,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={t('Meldingen')} />
            <MemberPageHeader
                eyebrow={t('Account')}
                title={t('Meldingen')}
                description={t(
                    'Uw persoonlijke meldingen en systeemberichten.',
                )}
            />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div
                    role="tablist"
                    className="inline-flex border border-gold/25 bg-choc3 p-1"
                >
                    {STATUS_TABS.map((tab) => (
                        <Link
                            key={tab.value}
                            role="tab"
                            aria-selected={status === tab.value}
                            href={memberNotifications.index(locale, {
                                query:
                                    tab.value === 'all'
                                        ? {}
                                        : { status: tab.value },
                            })}
                            preserveScroll
                            className={cn(
                                'inline-flex items-center gap-1.5 px-3 py-2 font-sans text-[10px] tracking-[0.18em] uppercase no-underline transition-colors',
                                status === tab.value
                                    ? 'bg-gold text-choc'
                                    : 'text-sand hover:bg-choc2 hover:text-cream',
                            )}
                        >
                            {t(tab.labelKey)}
                            {tab.value === 'unread' && unreadCount > 0 ? (
                                <span className="tabular-nums">
                                    ({unreadCount})
                                </span>
                            ) : null}
                        </Link>
                    ))}
                </div>

                <button
                    type="button"
                    onClick={markAllRead}
                    disabled={unreadCount === 0}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase transition-colors hover:text-cream disabled:cursor-not-allowed disabled:opacity-40"
                >
                    {t('Alles gelezen')}
                </button>
            </div>

            {rows.data.length === 0 ? (
                <MemberEmptyState
                    title={t('Geen meldingen gevonden.')}
                    description={t(
                        'Nieuwe meldingen verschijnen hier zodra ze binnenkomen.',
                    )}
                />
            ) : (
                <MemberPanel className="p-0 md:p-0">
                    <ul className="divide-y divide-gold/15">
                        {rows.data.map((item) => (
                            <li key={item.id}>
                                <Link
                                    href={memberNotifications.show({
                                        locale,
                                        userNotification: item.id,
                                    })}
                                    className={cn(
                                        'flex flex-col gap-1 px-5 py-4 no-underline transition-colors hover:bg-choc2 md:px-6',
                                        !item.read && 'bg-gold/10',
                                    )}
                                >
                                    <span className="flex items-center justify-between gap-3">
                                        <span
                                            className={cn(
                                                'truncate font-sans text-[13px] text-cream',
                                                item.read
                                                    ? 'font-normal'
                                                    : 'font-medium',
                                            )}
                                        >
                                            {item.title}
                                        </span>
                                        {!item.read ? (
                                            <span
                                                aria-label={t('Ongelezen')}
                                                className="size-2 shrink-0 rounded-full bg-gold"
                                            />
                                        ) : null}
                                    </span>
                                    {item.message ? (
                                        <span className="line-clamp-2 font-sans text-[12px] leading-relaxed text-sand">
                                            {item.message}
                                        </span>
                                    ) : null}
                                    <span className="font-sans text-[9px] tracking-[0.14em] text-sand/70 uppercase">
                                        {item.type_label
                                            ? `${item.type_label} · `
                                            : ''}
                                        {item.created_at_human}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </MemberPanel>
            )}

            {rows.last_page > 1 ? (
                <nav
                    aria-label={t('Paginering')}
                    className="mt-6 flex items-center justify-between gap-3"
                >
                    {rows.prev_page_url ? (
                        <Link
                            href={rows.prev_page_url}
                            preserveScroll
                            className={pagerClassName}
                        >
                            ← {t('Vorige')}
                        </Link>
                    ) : (
                        <span />
                    )}
                    <span className="font-sans text-[10px] tracking-[0.14em] text-sand tabular-nums">
                        {rows.current_page} / {rows.last_page}
                    </span>
                    {rows.next_page_url ? (
                        <Link
                            href={rows.next_page_url}
                            preserveScroll
                            className={pagerClassName}
                        >
                            {t('Volgende')} →
                        </Link>
                    ) : (
                        <span />
                    )}
                </nav>
            ) : null}
        </>
    );
}
