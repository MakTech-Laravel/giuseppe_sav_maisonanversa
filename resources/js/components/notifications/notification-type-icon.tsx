import {
    AlertTriangle,
    Bell,
    CheckCircle2,
    Info,
    Settings2,
    ShoppingBag,
    Siren,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { NotificationType } from '@/types/notification';

const ICONS: Record<NotificationType, LucideIcon> = {
    info: Info,
    success: CheckCircle2,
    warning: AlertTriangle,
    alert: Siren,
    order: ShoppingBag,
    community: UsersRound,
    system: Settings2,
};

const TONES: Record<NotificationType, string> = {
    info: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    alert: 'bg-red-500/10 text-red-600 dark:text-red-400',
    order: 'bg-primary/10 text-primary',
    community: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    system: 'bg-muted text-muted-foreground',
};

export function NotificationTypeIcon({
    type,
    className,
}: {
    type: NotificationType | null;
    className?: string;
}) {
    const Icon = type ? ICONS[type] : Bell;

    return (
        <span
            className={cn(
                'flex size-8 shrink-0 items-center justify-center rounded-full',
                type ? TONES[type] : 'bg-muted text-muted-foreground',
                className,
            )}
        >
            <Icon className="size-4" />
        </span>
    );
}
