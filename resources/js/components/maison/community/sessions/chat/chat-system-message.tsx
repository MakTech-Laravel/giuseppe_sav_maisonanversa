import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { ChatAttachment, ChatMessage } from '@/types/session-chat';

type ChatSystemMessageProps = {
    message: ChatMessage;
    className?: string;
};

/**
 * Centered transcript notice for staff chat-only posts. Not attributed to the
 * admin’s name — shown as a system line for every viewer.
 */
export function ChatSystemMessage({
    message,
    className,
}: ChatSystemMessageProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <div
            role="status"
            className={cn(
                'mx-auto flex w-full max-w-md flex-col items-center gap-2 px-4 py-1 text-center',
                className,
            )}
        >
            <span className="font-sans text-[10px] font-medium tracking-[0.2em] text-gold uppercase">
                {t('Systeem')}
            </span>

            {message.attachments.length > 0 && (
                <SystemImageGrid attachments={message.attachments} />
            )}

            {message.body ? (
                <p className="font-sans text-[12px] leading-relaxed whitespace-pre-wrap text-sand">
                    {message.body}
                </p>
            ) : null}

            <span className="font-sans text-[10px] tracking-[0.08em] text-sand/70">
                {formatSessionTime(message.sent_at, locale)}
            </span>
        </div>
    );
}

function SystemImageGrid({ attachments }: { attachments: ChatAttachment[] }) {
    return (
        <div
            className={cn(
                'grid w-full gap-1.5',
                attachments.length === 1 ? 'grid-cols-1' : 'grid-cols-2',
            )}
        >
            {attachments.map((attachment) => (
                <a
                    key={attachment.id}
                    href={attachment.url}
                    target="_blank"
                    rel="noreferrer"
                    className="block overflow-hidden border border-gold/20 bg-choc2"
                >
                    <img
                        src={attachment.url}
                        alt={attachment.name}
                        className="max-h-40 w-full object-cover"
                    />
                </a>
            ))}
        </div>
    );
}
