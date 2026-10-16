import { usePage } from '@inertiajs/react';
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
};

export function ChatMessageItem({
    message,
    isOwn,
    isFirstInGroup,
    isLastInGroup,
}: ChatMessageItemProps) {
    const { locale } = usePage().props;

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

                {isLastInGroup && (
                    <MessageFooter className="px-0 font-sans text-[10px] font-normal tracking-[0.08em] text-stone">
                        <time dateTime={message.sent_at}>
                            {formatSessionTime(message.sent_at, locale)}
                        </time>
                    </MessageFooter>
                )}
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
                <a
                    key={attachment.id}
                    href={attachment.url}
                    target="_blank"
                    rel="noreferrer"
                    className="block overflow-hidden border border-gold/25 bg-choc2"
                >
                    <img
                        src={attachment.url}
                        alt={attachment.name}
                        loading="lazy"
                        className="aspect-square w-full object-cover transition-opacity hover:opacity-90"
                    />
                </a>
            ))}
        </div>
    );
}
