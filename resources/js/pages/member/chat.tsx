import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, MessageCircle } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { ChatMembersButton } from '@/components/maison/community/sessions/chat/chat-members-button';
import { SessionChat } from '@/components/maison/community/sessions/chat/session-chat';
import { ChatSessionList } from '@/components/member/chat/chat-session-list';
import { MemberPageHeader } from '@/components/member/member-ui';
import { useSessionChatChannel } from '@/hooks/use-session-chat-channel';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import { setWatchingSessionChatId } from '@/lib/watching-session-chat';
import memberChat from '@/routes/member/chat';
import type { SessionCard, SessionPlayer } from '@/types/session';
import type { ChatMessage } from '@/types/session-chat';

type MemberChatPageProps = {
    sessions: SessionCard[];
    activeSession: SessionCard | null;
    viewer: SessionPlayer;
    messages: ChatMessage[];
    can_send: boolean;
    messages_purge_at: string | null;
    unread_counts: Record<string, number>;
};

export default function MemberChatPage({
    sessions,
    activeSession,
    viewer,
    messages: initialMessages,
    can_send: canSend,
    messages_purge_at: messagesPurgeAt,
    unread_counts: unreadCounts = {},
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
                        'w-full shrink-0 flex-col border-gold/20 md:flex md:w-56 md:border-r',
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
                        unreadCounts={unreadCounts}
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
                                canSend={canSend}
                                messagesPurgeAt={messagesPurgeAt}
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
 * Owns the transcript for one session. Remounted via key when the active
 * session changes so local state resets without an effect.
 */
function ActiveSessionChat({
    session,
    viewer,
    initialMessages,
    canSend,
    messagesPurgeAt,
}: {
    session: SessionCard;
    viewer: SessionPlayer;
    initialMessages: ChatMessage[];
    canSend: boolean;
    messagesPurgeAt: string | null;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [messages, setMessages] = useState<ChatMessage[]>(initialMessages);
    const localUrls = useRef<string[]>([]);
    const pendingOptimistic = useRef<Set<string>>(new Set());
    const pendingFiles = useRef<Map<string, File[]>>(new Map());

    useEffect(() => {
        setWatchingSessionChatId(session.id);
        const urls = localUrls.current;

        return () => {
            setWatchingSessionChatId(null);
            urls.forEach((url) => URL.revokeObjectURL(url));
        };
    }, [session.id]);

    const mergeIncoming = useCallback(
        (incoming: ChatMessage) => {
            // Sender already shows an optimistic bubble; Echo would duplicate it
            // until onSuccess. Other participants still get live updates here.
            if (Number(incoming.author.id) === Number(viewer.id)) {
                return;
            }

            setMessages((current) => {
                if (current.some((message) => message.id === incoming.id)) {
                    return current;
                }

                return [...current, incoming];
            });

            router.post(
                memberChat.read({
                    locale,
                    communitySession: Number(session.id),
                }).url,
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['chat', 'unread_counts'],
                },
            );
        },
        [locale, session.id, viewer.id],
    );

    useSessionChatChannel(session.id, mergeIncoming);

    function postMessage(
        optimisticId: string,
        messageBody: string | null,
        files: File[],
    ) {
        let storeUrl: string;

        try {
            storeUrl = memberChat.messages.store({
                locale,
                communitySession: Number(session.id),
            }).url;
        } catch {
            pendingOptimistic.current.delete(optimisticId);
            pendingFiles.current.delete(optimisticId);
            setMessages((current) =>
                current.map((message) =>
                    message.id === optimisticId
                        ? { ...message, status: 'failed' }
                        : message,
                ),
            );
            toast.error(t('Bericht kon niet worden verstuurd.'));

            return;
        }

        const markFailed = () => {
            pendingOptimistic.current.delete(optimisticId);
            setMessages((current) =>
                current.map((message) =>
                    message.id === optimisticId
                        ? { ...message, status: 'failed' }
                        : message,
                ),
            );
            toast.error(t('Bericht kon niet worden verstuurd.'));
        };

        const payload =
            files.length > 0
                ? { body: messageBody ?? '', images: files }
                : { body: messageBody ?? '' };

        router.post(storeUrl, payload, {
            forceFormData: files.length > 0,
            preserveScroll: true,
            preserveState: true,
            only: [
                'messages',
                'can_send',
                'messages_purge_at',
                'unread_counts',
                'chat',
            ],
            onSuccess: (page) => {
                const serverMessages = page.props.messages as
                    | ChatMessage[]
                    | undefined;

                pendingOptimistic.current.delete(optimisticId);
                pendingFiles.current.delete(optimisticId);

                if (Array.isArray(serverMessages)) {
                    setMessages(serverMessages);

                    return;
                }

                setMessages((current) =>
                    current.filter((message) => message.id !== optimisticId),
                );
            },
            onError: markFailed,
            onCancel: markFailed,
        });
    }

    function handleSend(body: string, files: File[]) {
        const sentAt = new Date().toISOString();
        const optimisticId = `local-${sentAt}-${messages.length}`;
        const messageBody = body === '' ? null : body;
        pendingOptimistic.current.add(optimisticId);
        pendingFiles.current.set(optimisticId, files);

        const attachments = files.map((file, index) => {
            const url = URL.createObjectURL(file);
            localUrls.current.push(url);

            return { id: `${optimisticId}-${index}`, url, name: file.name };
        });

        setMessages((current) => [
            ...current,
            {
                id: optimisticId,
                author: viewer,
                body: messageBody,
                attachments,
                sent_at: sentAt,
                is_system: false,
                status: 'sending',
            },
        ]);

        postMessage(optimisticId, messageBody, files);
    }

    function handleRetry(message: ChatMessage) {
        const files = pendingFiles.current.get(message.id) ?? [];
        pendingOptimistic.current.add(message.id);

        setMessages((current) =>
            current.map((row) =>
                row.id === message.id ? { ...row, status: 'sending' } : row,
            ),
        );

        postMessage(message.id, message.body, files);
    }

    function handleDismissFailed(message: ChatMessage) {
        pendingOptimistic.current.delete(message.id);
        pendingFiles.current.delete(message.id);
        setMessages((current) =>
            current.filter((row) => row.id !== message.id),
        );
    }

    const clubName = session.club?.name ?? t('Sessie');
    const purgeLabel = messagesPurgeAt
        ? formatSessionDateLong(messagesPurgeAt, locale)
        : null;

    const closedMessage = session.is_cancelled
        ? purgeLabel
            ? t(
                  'Deze sessie is geannuleerd. De chat is gesloten. Berichten worden op {{date}} verwijderd (30 dagen na afsluiting).',
                  { date: purgeLabel },
              )
            : t('Deze sessie is geannuleerd. De chat is gesloten.')
        : purgeLabel
          ? t(
                'Deze sessie is afgelopen. De chat is gesloten. Berichten worden op {{date}} verwijderd (30 dagen na afsluiting).',
                { date: purgeLabel },
            )
          : t('Deze sessie is afgelopen. De chat is gesloten.');

    function handleReport(message: ChatMessage) {
        router.post(
            memberChat.messages.report.url({
                locale,
                communitySession: Number(session.id),
                message: Number(message.id),
            }),
            {},
            { preserveScroll: true },
        );
    }

    return (
        <SessionChat
            messages={messages}
            currentUserId={viewer.id}
            canSend={canSend}
            onSend={handleSend}
            onRetry={handleRetry}
            onDismissFailed={handleDismissFailed}
            onReport={handleReport}
            title={clubName}
            subtitle={`${formatSessionDateLong(session.starts_at, locale)} · ${formatSessionTime(session.starts_at, locale)}`}
            headerAside={<ChatMembersButton players={session.players} />}
            closedMessage={closedMessage}
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
