import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, MessageCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { SessionChat } from '@/components/maison/community/sessions/chat/session-chat';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { ChatSessionList } from '@/components/member/chat/chat-session-list';
import { MemberPageHeader } from '@/components/member/member-ui';
import { isSessionChatOpen } from '@/lib/session-chat';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import * as memberChat from '@/routes/member/chat';
import type { SessionCard, SessionPlayer } from '@/types/session';
import type { ChatMessage } from '@/types/session-chat';

type MemberChatPageProps = {
    sessions: SessionCard[];
    activeSession: SessionCard | null;
    viewer: SessionPlayer;
    messages: ChatMessage[];
};

export default function MemberChatPage({
    sessions,
    activeSession,
    viewer,
    messages: initialMessages,
}: MemberChatPageProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const activeId = activeSession?.id ?? null;

    return (
        <>
            <Head title={t('Chat')} />

            <MemberPageHeader
                eyebrow={t('Gemeenschap')}
                title={t('Chat')}
                description={t(
                    'Berichten met de spelers van uw sessies. Selecteer een sessie om te beginnen.',
                )}
            />

            <div className="flex h-[min(760px,calc(100dvh-14rem))] min-h-120 overflow-hidden border border-gold/25 bg-choc3 text-cream">
                <aside
                    className={cn(
                        'w-full shrink-0 flex-col border-gold/20 md:flex md:w-80 md:border-r',
                        activeSession ? 'hidden' : 'flex',
                    )}
                >
                    <div className="border-b border-gold/20 px-5 py-4">
                        <h2 className="font-sans text-[10px] font-medium tracking-[0.22em] text-gold uppercase">
                            {t('Sessies')}
                        </h2>
                    </div>
                    <ChatSessionList
                        sessions={sessions}
                        activeSessionId={activeId}
                        hrefFor={(session) =>
                            memberChat.show({
                                locale,
                                communitySession: Number(session.id),
                            }).url
                        }
                    />
                </aside>

                <div
                    className={cn(
                        'min-w-0 flex-1 flex-col',
                        activeSession ? 'flex' : 'hidden md:flex',
                    )}
                >
                    {activeSession ? (
                        <>
                            <div className="border-b border-gold/20 px-4 py-3 md:hidden">
                                <Link
                                    href={memberChat.index(locale).url}
                                    className="inline-flex items-center gap-2 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:text-gold"
                                >
                                    <ArrowLeft
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {t('Terug naar chats')}
                                </Link>
                            </div>

                            <ActiveSessionChat
                                key={activeSession.id}
                                session={activeSession}
                                viewer={viewer}
                                initialMessages={initialMessages}
                            />
                        </>
                    ) : (
                        <ChatEmptySelection />
                    )}
                </div>
            </div>
        </>
    );
}

/**
 * Owns the local transcript for one session. Remounted via key when the
 * active session changes so messages reset without an effect.
 */
function ActiveSessionChat({
    session,
    viewer,
    initialMessages,
}: {
    session: SessionCard;
    viewer: SessionPlayer;
    initialMessages: ChatMessage[];
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [messages, setMessages] = useState<ChatMessage[]>(initialMessages);
    const localUrls = useRef<string[]>([]);

    useEffect(
        () => () =>
            localUrls.current.forEach((url) => URL.revokeObjectURL(url)),
        [],
    );

    function handleSend(body: string, files: File[]) {
        const sentAt = new Date().toISOString();
        const id = `local-${sentAt}-${messages.length}`;

        const attachments = files.map((file, index) => {
            const url = URL.createObjectURL(file);
            localUrls.current.push(url);

            return { id: `${id}-${index}`, url, name: file.name };
        });

        setMessages((current) => [
            ...current,
            {
                id,
                author: viewer,
                body: body === '' ? null : body,
                attachments,
                sent_at: sentAt,
            },
        ]);
    }

    const clubName = session.club?.name ?? t('Sessie');

    return (
        <SessionChat
            messages={messages}
            currentUserId={viewer.id}
            canSend={isSessionChatOpen(session)}
            onSend={handleSend}
            title={clubName}
            subtitle={`${formatSessionDateLong(session.starts_at, locale)} · ${formatSessionTime(session.starts_at, locale)}`}
            headerAside={
                <PlayerSlots players={session.players} openSlots={0} />
            }
            closedMessage={
                session.is_cancelled
                    ? t('Deze sessie is geannuleerd. De chat is gesloten.')
                    : t('Deze sessie is afgelopen. De chat is gesloten.')
            }
            className="min-h-0 flex-1 border-0"
        />
    );
}

function ChatEmptySelection() {
    const { t } = useTranslation();

    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-14 text-center">
            <MessageCircle className="size-7 text-gold2" aria-hidden="true" />
            <p className="font-serif text-xl text-cream">
                {t('Selecteer een sessie')}
            </p>
            <p className="max-w-sm font-sans text-[12px] text-sand">
                {t(
                    'Kies links een sessie om de chat te openen en berichten te versturen.',
                )}
            </p>
        </div>
    );
}
