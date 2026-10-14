import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Bell, ExternalLink, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import adminNotifications from '@/routes/admin/notifications';
import type { NotificationItem } from '@/types/notification';

export default function NotificationShow({
    notification,
}: {
    notification: NotificationItem;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={notification.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('Melding')} icon={Bell}>
                    <Button asChild variant="outline">
                        <Link href={adminNotifications.index(locale)}>
                            <ArrowLeft className="h-4 w-4" />
                            {t('Alle meldingen')}
                        </Link>
                    </Button>
                    <ConfirmDeleteDialog
                        description={t(
                            'Deze melding uit uw inbox verwijderen?',
                        )}
                        onConfirm={() =>
                            router.delete(
                                adminNotifications.destroy({
                                    locale,
                                    userNotification: notification.id,
                                }).url,
                            )
                        }
                    >
                        <Button variant="outline" className="text-destructive">
                            <Trash2 className="h-4 w-4" />
                            {t('Verwijderen')}
                        </Button>
                    </ConfirmDeleteDialog>
                </AdminPageHeader>

                <article className="max-w-3xl rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-start gap-4">
                        <NotificationTypeIcon
                            type={notification.type}
                            className="size-10"
                        />
                        <div className="min-w-0 flex-1 space-y-1">
                            <h2 className="text-lg font-semibold">
                                {notification.title}
                            </h2>
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                {notification.type_label ? (
                                    <Badge variant="outline">
                                        {notification.type_label}
                                    </Badge>
                                ) : null}
                                <Badge
                                    variant={
                                        notification.read
                                            ? 'secondary'
                                            : 'default'
                                    }
                                >
                                    {notification.read
                                        ? t('Gelezen')
                                        : t('Ongelezen')}
                                </Badge>
                                {notification.created_at ? (
                                    <span>
                                        {new Date(
                                            notification.created_at,
                                        ).toLocaleString()}
                                    </span>
                                ) : null}
                            </div>
                        </div>
                    </div>
                    <p className="mt-6 text-sm leading-relaxed whitespace-pre-line">
                        {notification.message}
                    </p>
                    {notification.href ? (
                        <Button asChild className="mt-6">
                            <a href={notification.href}>
                                <ExternalLink className="h-4 w-4" />
                                {notification.link_label ?? t('Openen')}
                            </a>
                        </Button>
                    ) : null}
                </article>
            </div>
        </>
    );
}

NotificationShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Meldingen',
            href: adminNotifications.index(wayfinderLocale()),
        },
    ],
};
