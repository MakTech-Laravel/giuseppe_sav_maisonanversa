import { Lock } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

type ChatClosedNoticeProps = {
    message?: string;
    className?: string;
};

/**
 * Replaces the composer once a conversation is read-only: the transcript stays
 * visible, but nobody can post to it anymore.
 */
export function ChatClosedNotice({
    message,
    className,
}: ChatClosedNoticeProps) {
    const { t } = useTranslation();

    return (
        <div
            role="status"
            className={cn(
                'flex items-center justify-center gap-2 border-t border-gold/20 bg-choc2 px-6 py-5 text-center font-sans text-[11px] tracking-[0.08em] text-sand',
                className,
            )}
        >
            <Lock className="size-3.5 shrink-0" aria-hidden="true" />
            {message ??
                t(
                    'Deze chat is gesloten. Er kunnen geen berichten meer worden verstuurd.',
                )}
        </div>
    );
}
