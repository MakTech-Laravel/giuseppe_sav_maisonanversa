import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';

export function InquirySlaBadge({
    priority,
    slaDueAt,
    slaBreached,
    seen,
}: {
    priority: boolean;
    slaDueAt: string | null;
    slaBreached: boolean;
    seen: boolean;
}) {
    const { t } = useTranslation();

    if (!priority) {
        return null;
    }

    const remaining = remainingLabel(slaDueAt, t);
    const overdue = slaBreached || remaining === null;

    return (
        <Badge variant={overdue && !seen ? 'destructive' : 'secondary'}>
            {seen
                ? t('Binnen SLA')
                : overdue
                  ? t('SLA overschreden')
                  : remaining}
        </Badge>
    );
}

function remainingLabel(
    dueAt: string | null,
    t: ReturnType<typeof useTranslation>['t'],
): string | null {
    if (!dueAt) {
        return null;
    }

    const ms = new Date(dueAt).getTime() - Date.now();

    if (Number.isNaN(ms) || ms <= 0) {
        return null;
    }

    const hours = Math.floor(ms / 3_600_000);
    const minutes = Math.floor((ms % 3_600_000) / 60_000);

    return t('{{hours}}u {{minutes}}m resterend', { hours, minutes });
}
