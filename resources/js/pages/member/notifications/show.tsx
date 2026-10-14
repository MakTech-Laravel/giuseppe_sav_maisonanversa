import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberPageHeader,
    MemberPanel,
    MemberStatusPill,
} from '@/components/member/member-ui';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import memberNotifications from '@/routes/member/notifications';
import type { NotificationItem } from '@/types/notification';

export default function MemberNotificationShow({
    notification,
}: {
    notification: NotificationItem;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    const destroy = () => {
        if (!window.confirm(t('Deze melding uit uw inbox verwijderen?'))) {
            return;
        }

        router.delete(
            memberNotifications.destroy({
                locale,
                userNotification: notification.id,
            }).url,
        );
    };

    return (
        <>
            <Head title={notification.title} />
            <div className="mb-6">
                <Link
                    href={memberNotifications.index(locale)}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline hover:text-cream"
                >
                    ← {t('Terug naar meldingen')}
                </Link>
            </div>

            <MemberPageHeader
                eyebrow={notification.type_label ?? t('Melding')}
                title={notification.title}
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <MemberStatusPill
                    tone={notification.read ? 'neutral' : 'success'}
                >
                    {notification.read ? t('Gelezen') : t('Ongelezen')}
                </MemberStatusPill>
                {notification.created_at ? (
                    <span className="font-sans text-[10px] tracking-[0.14em] text-sand uppercase">
                        {new Date(notification.created_at).toLocaleString(
                            locale,
                        )}
                    </span>
                ) : null}
            </div>

            <MemberPanel className="max-w-3xl">
                <p className="text-[15px] leading-[1.8] whitespace-pre-line text-cream">
                    {notification.message}
                </p>

                <div className="mt-8 flex flex-wrap items-center gap-4 border-t border-gold/20 pt-6">
                    {notification.href ? (
                        <a
                            href={notification.href}
                            className="inline-flex min-h-11 items-center bg-gold px-5 font-sans text-[10px] tracking-[0.2em] text-choc uppercase no-underline transition-colors hover:bg-gold2 hover:text-cream"
                        >
                            {notification.link_label ?? t('Openen')}
                        </a>
                    ) : null}
                    <button
                        type="button"
                        onClick={destroy}
                        className="inline-flex min-h-11 items-center font-sans text-[10px] tracking-[0.2em] text-sand uppercase transition-colors hover:text-cream"
                    >
                        {t('Verwijderen')}
                    </button>
                </div>
            </MemberPanel>
        </>
    );
}
