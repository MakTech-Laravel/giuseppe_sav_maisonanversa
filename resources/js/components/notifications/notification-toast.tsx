import { BellRing } from 'lucide-react';
import { toast } from 'sonner';
import type { NotificationItem } from '@/types/notification';

type Translate = (key: string) => string;

function NotificationToastTitle({
    payload,
    eyebrow,
}: {
    payload: NotificationItem;
    eyebrow: string;
}) {
    return (
        <span className="flex flex-col gap-0.5">
            <span className="text-[10px] font-medium tracking-[0.16em] text-white/70 uppercase">
                {payload.type_label
                    ? `${eyebrow} · ${payload.type_label}`
                    : eyebrow}
            </span>
            <span className="text-sm font-semibold text-white">
                {payload.title?.trim() || eyebrow}
            </span>
        </span>
    );
}

/**
 * Branded toast for in-app notifications only; per-toast styling keeps the
 * global richColors look for flash, success and error toasts.
 */
export function showNotificationToast(
    payload: NotificationItem,
    t: Translate,
): void {
    const href = payload.href;

    toast(
        <NotificationToastTitle
            payload={payload}
            eyebrow={t('Nieuwe melding')}
        />,
        {
            id: `inbox-${payload.id}`,
            description: payload.message?.trim() || undefined,
            icon: <BellRing className="size-4 text-gold" />,
            richColors: false,
            duration: 8000,
            style: {
                background: 'var(--color-choc)',
                color: '#fff',
                borderColor: 'var(--color-gold)',
            },
            classNames: {
                toast: 'border-l-4 shadow-xl',
                description: '!text-white/80',
                actionButton: '!bg-gold !text-white',
                closeButton: '!bg-choc2 !text-white !border-gold/40',
            },
            action: href
                ? {
                      label: payload.link_label || t('Openen'),
                      onClick: () => window.location.assign(href),
                  }
                : undefined,
        },
    );
}
