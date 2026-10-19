import { useTranslation } from 'react-i18next';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useEchoConnection } from '@/hooks/use-echo-connection';
import { cn } from '@/lib/utils';

export function ReverbStatusIndicator() {
    const { t } = useTranslation();
    const { status } = useEchoConnection();

    const connected = status === 'connected';
    const connecting = status === 'connecting';
    const label = connected
        ? t('Verbonden')
        : connecting
          ? t('Verbinden…')
          : t('Verbroken');

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    aria-label={`Reverb: ${label}`}
                    data-test="admin-reverb-status"
                    data-status={status}
                    className="inline-flex h-8 cursor-default items-center gap-2 rounded-md border border-border px-2 text-xs text-muted-foreground transition-colors hover:text-foreground"
                >
                    <span className="relative flex size-2.5">
                        {connected ? (
                            <span className="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-60" />
                        ) : null}
                        <span
                            className={cn(
                                'relative inline-flex size-2.5 rounded-full',
                                connected
                                    ? 'bg-emerald-500'
                                    : connecting
                                      ? 'bg-amber-500'
                                      : 'bg-destructive',
                            )}
                        />
                    </span>
                    <span className="hidden sm:inline">Reverb</span>
                </button>
            </TooltipTrigger>
            <TooltipContent side="bottom" className="text-white">{label}</TooltipContent>
        </Tooltip>
    );
}
