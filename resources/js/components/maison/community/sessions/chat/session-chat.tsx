import type { ReactNode } from 'react';
import { ChatClosedNotice } from '@/components/maison/community/sessions/chat/chat-closed-notice';
import { ChatComposer } from '@/components/maison/community/sessions/chat/chat-composer';
import { ChatMessageList } from '@/components/maison/community/sessions/chat/chat-message-list';
import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types/session-chat';

type SessionChatProps = {
    messages: ChatMessage[];
    currentUserId: number;
    canSend: boolean;
    onSend?: (body: string, files: File[]) => void;
    title?: string;
    subtitle?: string;
    /** Rendered at the right of the header, e.g. a participant stack. */
    headerAside?: ReactNode;
    closedMessage?: string;
    className?: string;
};

/**
 * A complete conversation panel: header, transcript, and either the composer
 * or a closed notice. Knows nothing about where messages come from, so the
 * same panel can serve any conversation.
 */
export function SessionChat({
    messages,
    currentUserId,
    canSend,
    onSend,
    title,
    subtitle,
    headerAside,
    closedMessage,
    className,
}: SessionChatProps) {
    return (
        <section
            className={cn(
                'flex min-h-0 flex-1 flex-col border border-gold/25 bg-choc3 text-cream',
                className,
            )}
        >
            {(title || subtitle || headerAside) && (
                <header className="flex flex-wrap items-center justify-between gap-4 border-b border-gold/20 px-4 py-2">
                    <div className="min-w-0">
                        {title && (
                            <h2 className="truncate font-serif text-[20px] font-medium text-cream">
                                {title}
                            </h2>
                        )}
                        {subtitle && (
                            <p className="mt-1 font-sans text-[11px] tracking-[0.08em] text-sand">
                                {subtitle}
                            </p>
                        )}
                    </div>
                    {headerAside}
                </header>
            )}

            <ChatMessageList
                messages={messages}
                currentUserId={currentUserId}
            />

            {canSend && onSend ? (
                <ChatComposer onSend={onSend} />
            ) : (
                <ChatClosedNotice message={closedMessage} />
            )}
        </section>
    );
}
