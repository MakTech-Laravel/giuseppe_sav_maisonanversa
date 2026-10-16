import { usePage } from '@inertiajs/react';
import { Check, CircleAlert, ImageOff, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import {
    Message,
    MessageAvatar,
    MessageContent,
    MessageFooter,
    MessageHeader,
} from '@/components/ui/message';
import { formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { ChatAttachment, ChatMessage } from '@/types/session-chat';

type ChatMessageItemProps = {
    message: ChatMessage;
    isOwn: boolean;
    /** The first message of a run from one author carries the name. */
    isFirstInGroup: boolean;
    /** The last message of a run carries the avatar and the time. */
    isLastInGroup: boolean;
    onRetry?: (message: ChatMessage) => void;
    onDismissFailed?: (message: ChatMessage) => void;
};

export function ChatMessageItem({
    message,
    isOwn,
    isFirstInGroup,
    isLastInGroup,
    onRetry,
    onDismissFailed,
}: ChatMessageItemProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const status = message.status ?? 'sent';
    const failed = isOwn && status === 'failed';
    const sending = isOwn && status === 'sending';
    const showOwnFooter = isOwn && (failed || sending || isLastInGroup);
    const showOtherFooter = !isOwn && isLastInGroup;

    return (
        <Message align={isOwn ? 'end' : 'start'}>
            {!isOwn && (
                <MessageAvatar className="min-w-9 bg-transparent group-has-data-[slot=message-footer]/message:-translate-y-6">
                    {isLastInGroup && (
                        <Monogram
                            size="sm"
                            initials={message.author.initials}
                            title={message.author.name}
                        >
                            {message.author.avatar_url ? (
                                <img
                                    src={message.author.avatar_url}
                                    alt={message.author.name}
                                    className="size-full object-cover"
                                />
                            ) : undefined}
                        </Monogram>
                    )}
                </MessageAvatar>
            )}

            <MessageContent className="gap-1.5">
                {!isOwn && isFirstInGroup && (
                    <MessageHeader className="px-0 font-sans text-[10px] font-medium tracking-[0.16em] text-sand uppercase">
                        {message.author.name}
                    </MessageHeader>
                )}

                {message.attachments.length > 0 && (
                    <ChatImageGrid attachments={message.attachments} />
                )}

                {message.body && (
                    <Bubble
                        variant={isOwn ? 'default' : 'outline'}
                        className={cn(
                            'max-w-[85%] md:max-w-[70%]',
                            isOwn
                                ? '*:data-[slot=bubble-content]:bg-gold *:data-[slot=bubble-content]:text-choc'
                                : '*:data-[slot=bubble-content]:border-gold/25 *:data-[slot=bubble-content]:bg-choc2 *:data-[slot=bubble-content]:text-cream',
                        )}
                    >
                        <BubbleContent className="rounded-none font-sans text-[13px] leading-relaxed whitespace-pre-wrap">
                            {message.body}
                        </BubbleContent>
                    </Bubble>
                )}

                {failed ? (
                    <div className="flex flex-wrap items-center justify-end gap-2 px-0 font-sans text-[10px] tracking-[0.08em]">
                        <span className="inline-flex items-center gap-1 text-destructive">
                            <CircleAlert
                                className="size-3 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{t('Versturen mislukt')}</span>
                        </span>
                        {onRetry ? (
                            <button
                                type="button"
                                onClick={() => onRetry(message)}
                                className="text-gold underline-offset-2 hover:underline"
                            >
                                {t('Opnieuw')}
                            </button>
                        ) : null}
                        {onDismissFailed ? (
                            <button
                                type="button"
                                onClick={() => onDismissFailed(message)}
                                className="text-sand underline-offset-2 hover:underline"
                            >
                                {t('Verwijderen')}
                            </button>
                        ) : null}
                    </div>
                ) : showOwnFooter ? (
                    <MessageFooter className="justify-end gap-1.5 px-0 font-sans text-[10px] font-normal tracking-[0.08em] text-stone">
                        <time dateTime={message.sent_at}>
                            {formatSessionTime(message.sent_at, locale)}
                        </time>
                        {sending ? (
                            <Loader2
                                className="size-3 shrink-0 animate-spin text-sand"
                                aria-label={t('Versturen…')}
                            />
                        ) : (
                            <Check
                                className="size-3.5 shrink-0 text-gold"
                                aria-label={t('Verstuurd')}
                            />
                        )}
                    </MessageFooter>
                ) : showOtherFooter ? (
                    <MessageFooter className="px-0 font-sans text-[10px] font-normal tracking-[0.08em] text-stone">
                        <time dateTime={message.sent_at}>
                            {formatSessionTime(message.sent_at, locale)}
                        </time>
                    </MessageFooter>
                ) : null}
            </MessageContent>
        </Message>
    );
}

function ChatImageGrid({ attachments }: { attachments: ChatAttachment[] }) {
    return (
        <div
            data-slot="message-attachments"
            className={cn(
                'grid gap-1.5',
                attachments.length > 1
                    ? 'w-full max-w-xs grid-cols-2'
                    : 'w-full max-w-60 grid-cols-1',
            )}
        >
            {attachments.map((attachment) => (
                <ChatImageThumb key={attachment.id} attachment={attachment} />
            ))}
        </div>
    );
}

function ChatImageThumb({ attachment }: { attachment: ChatAttachment }) {
    const { t } = useTranslation();
    const [broken, setBroken] = useState(false);

    return (
        <a
            href={attachment.url}
            target="_blank"
            rel="noreferrer"
            className="block overflow-hidden border border-gold/25 bg-choc2"
        >
            {broken ? (
                <span
                    className="flex aspect-square w-full flex-col items-center justify-center gap-1.5 px-2 text-center"
                    role="img"
                    aria-label={t('Afbeelding niet beschikbaar')}
                >
                    <ImageOff className="size-5 text-sand" aria-hidden="true" />
                    <span className="line-clamp-2 font-sans text-[10px] text-sand">
                        {attachment.name}
                    </span>
                </span>
            ) : (
                <img
                    src={attachment.url}
                    alt={attachment.name}
                    loading="lazy"
                    onError={() => setBroken(true)}
                    className="aspect-square w-full object-contain transition-opacity hover:opacity-90"
                />
            )}
        </a>
    );
}
