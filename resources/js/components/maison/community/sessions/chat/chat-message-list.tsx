import { usePage } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { ChatMessageItem } from '@/components/maison/community/sessions/chat/chat-message-item';
import { MessageGroup } from '@/components/ui/message';
import {
    MessageScroller,
    MessageScrollerButton,
    MessageScrollerContent,
    MessageScrollerItem,
    MessageScrollerProvider,
    MessageScrollerViewport,
    useMessageScroller,
} from '@/components/ui/message-scroller';
import { formatSessionDateLong } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types/session-chat';

type ChatMessageListProps = {
    messages: ChatMessage[];
    currentUserId: number;
    emptyState?: ReactNode;
    className?: string;
    onRetry?: (message: ChatMessage) => void;
    onDismissFailed?: (message: ChatMessage) => void;
};

type ChatRow =
    | { kind: 'day'; id: string; sentAt: string }
    | { kind: 'group'; id: string; authorId: number; messages: ChatMessage[] };

/**
 * The scrollable transcript: day separators, then runs of consecutive
 * messages from one author. Opens at the latest message and follows new ones
 * while the reader stays at the bottom.
 */
export function ChatMessageList({
    messages,
    currentUserId,
    emptyState,
    className,
    onRetry,
    onDismissFailed,
}: ChatMessageListProps) {
    const { t } = useTranslation();
    const rows = useMemo(() => buildRows(messages), [messages]);

    return (
        <MessageScrollerProvider autoScroll defaultScrollPosition="end">
            <MessageScroller className={cn('min-h-0 flex-1', className)}>
                <MessageScrollerViewport aria-label={t('Berichten')}>
                    <MessageScrollerContent className="gap-5 px-5 py-6 md:px-7">
                        {rows.length === 0 ? (
                            <MessageScrollerItem className="my-auto">
                                {emptyState ?? <ChatEmptyState />}
                            </MessageScrollerItem>
                        ) : (
                            rows.map((row) => (
                                <MessageScrollerItem
                                    key={row.id}
                                    messageId={row.id}
                                >
                                    {row.kind === 'day' ? (
                                        <ChatDaySeparator sentAt={row.sentAt} />
                                    ) : (
                                        <MessageGroup className="gap-1.5">
                                            {row.messages.map(
                                                (message, index) => (
                                                    <ChatMessageItem
                                                        key={message.id}
                                                        message={message}
                                                        isOwn={
                                                            row.authorId ===
                                                            currentUserId
                                                        }
                                                        isFirstInGroup={
                                                            index === 0
                                                        }
                                                        isLastInGroup={
                                                            index ===
                                                            row.messages
                                                                .length -
                                                                1
                                                        }
                                                        onRetry={onRetry}
                                                        onDismissFailed={
                                                            onDismissFailed
                                                        }
                                                    />
                                                ),
                                            )}
                                        </MessageGroup>
                                    )}
                                </MessageScrollerItem>
                            ))
                        )}
                    </MessageScrollerContent>
                </MessageScrollerViewport>
                <MessageScrollerButton
                    variant="outline"
                    className="rounded-none border-gold/30 bg-choc2 text-cream hover:bg-gold/15 hover:text-gold"
                />
            </MessageScroller>

            <FollowOwnMessages
                messages={messages}
                currentUserId={currentUserId}
            />
        </MessageScrollerProvider>
    );
}

/**
 * Sending always brings the writer back to the latest message, even when they
 * had scrolled up to read.
 */
function FollowOwnMessages({
    messages,
    currentUserId,
}: {
    messages: ChatMessage[];
    currentUserId: number;
}) {
    const { scrollToEnd } = useMessageScroller();
    const previousCount = useRef(messages.length);

    useEffect(() => {
        const latest = messages.at(-1);
        const grew = messages.length > previousCount.current;

        previousCount.current = messages.length;

        if (grew && latest?.author.id === currentUserId) {
            scrollToEnd({ behavior: 'smooth' });
        }
    }, [messages, currentUserId, scrollToEnd]);

    return null;
}

function ChatDaySeparator({ sentAt }: { sentAt: string }) {
    const { locale } = usePage().props;

    return (
        <div className="flex items-center gap-4" role="separator">
            <span className="h-px flex-1 bg-gold/15" aria-hidden="true" />
            <span className="font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                {formatSessionDateLong(sentAt, locale)}
            </span>
            <span className="h-px flex-1 bg-gold/15" aria-hidden="true" />
        </div>
    );
}

function ChatEmptyState() {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <MessageCircle className="size-6 text-gold2" aria-hidden="true" />
            <p className="font-serif text-lg text-cream">
                {t('Nog geen berichten')}
            </p>
            <p className="max-w-sm font-sans text-[12px] text-sand">
                {t('Stuur het eerste bericht om het gesprek te starten.')}
            </p>
        </div>
    );
}

function buildRows(messages: ChatMessage[]): ChatRow[] {
    const rows: ChatRow[] = [];
    let currentDay: string | null = null;
    let currentGroup: Extract<ChatRow, { kind: 'group' }> | null = null;

    for (const message of messages) {
        const day = new Date(message.sent_at).toDateString();

        if (day !== currentDay) {
            currentDay = day;
            currentGroup = null;
            rows.push({
                kind: 'day',
                id: `day-${day}`,
                sentAt: message.sent_at,
            });
        }

        if (currentGroup && currentGroup.authorId === message.author.id) {
            currentGroup.messages.push(message);

            continue;
        }

        currentGroup = {
            kind: 'group',
            id: `group-${message.id}`,
            authorId: message.author.id,
            messages: [message],
        };
        rows.push(currentGroup);
    }

    return rows;
}
