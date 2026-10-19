import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, MessagesSquare } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ChatMembersButton } from '@/components/maison/community/sessions/chat/chat-members-button';
import { SessionChat } from '@/components/maison/community/sessions/chat/session-chat';
import { ChatSessionList } from '@/components/member/chat/chat-session-list';
import { useSessionChatChannel } from '@/hooks/use-session-chat-channel';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import adminChat from '@/routes/admin/chat';
import type { SessionCard, SessionPlayer } from '@/types/session';
import type { ChatMessage } from '@/types/session-chat';

type AdminChatPageProps = {
    sessions: SessionCard[];
    activeSession: SessionCard | null;
    viewer: SessionPlayer;
    messages: ChatMessage[];
    can_send: boolean;
    messages_purge_at: string | null;
};

export default function AdminChatPage({
    sessions,
    activeSession,
    viewer,
    messages: initialMessages,
    can_send: canSend,
    messages_purge_at: messagesPurgeAt,
}: AdminChatPageProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const activeId = activeSession?.id ?? null;

    return (
        <>
            <Head title={t('Chat')} />

            <div className="flex h-full min-h-0 w-full flex-1 flex-col space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Chat')}
                    description={t(
                        'Bekijk en beantwoord sessiechats. Bij een open sessie kunt u berichten versturen.',
                    )}
                    icon={MessagesSquare}
                />

                <div className="flex min-h-120 flex-1 overflow-hidden border border-gold/25 bg-choc3 text-cream">
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
                            hrefFor={(session) =>
                                adminChat.show({
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
                                        href={adminChat.index(locale).url}
                                        className="inline-flex items-center gap-2 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:text-gold"
                                    >
                                        <ArrowLeft
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {t('Terug naar chats')}
                                    </Link>
                                </div>

                                <AdminActiveSessionChat
                                    key={activeSession.id}
                                    session={activeSession}
                                    viewer={viewer}
                                    initialMessages={initialMessages}
                                    canSend={canSend}
                                    messagesPurgeAt={messagesPurgeAt}
                                />
                            </>
                        ) : (
                            <AdminChatEmptySelection />
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function AdminActiveSessionChat({
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
    const locale = wayfinderLocale();
    const [messages, setMessages] = useState<ChatMessage[]>(initialMessages);
    const localUrls = useRef<string[]>([]);
    const pendingOptimistic = useRef<Set<string>>(new Set());
    const pendingFiles = useRef<Map<string, File[]>>(new Map());

    useEffect(
        () => () =>
            localUrls.current.forEach((url) => URL.revokeObjectURL(url)),
        [],
    );

    const mergeIncoming = useCallback(
        (incoming: ChatMessage) => {
            if (Number(incoming.author.id) === Number(viewer.id)) {
                return;
            }

            setMessages((current) => {
                if (current.some((message) => message.id === incoming.id)) {
                    return current;
                }

                return [...current, incoming];
            });
        },
        [viewer.id],
    );

    useSessionChatChannel(session.id, mergeIncoming);

    function postMessage(
        optimisticId: string,
        messageBody: string | null,
        files: File[],
    ) {
        let storeUrl: string;

        try {
            storeUrl = adminChat.messages.store({
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
            only: ['messages', 'can_send', 'messages_purge_at'],
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
                status: 'sending',
                // Chat-only staff seats render as system; play joins keep bubbles.
                is_system: !session.is_joined,
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

    const purgeLabel = messagesPurgeAt
        ? formatSessionDateLong(messagesPurgeAt, locale)
        : null;

    const closedMessage = purgeLabel
        ? t(
              'Deze chat is gesloten. Berichten worden op {{date}} verwijderd (7 dagen na afsluiting).',
              { date: purgeLabel },
          )
        : t(
              'Deze chat is gesloten. Er kunnen geen berichten meer worden verstuurd.',
          );

    return (
        <SessionChat
            messages={messages}
            currentUserId={viewer.id}
            canSend={canSend}
            onSend={handleSend}
            onRetry={handleRetry}
            onDismissFailed={handleDismissFailed}
            title={session.club?.name ?? t('Sessie')}
            subtitle={`${formatSessionDateLong(session.starts_at, locale)} · ${formatSessionTime(session.starts_at, locale)}`}
            headerAside={<ChatMembersButton players={session.players} />}
            closedMessage={closedMessage}
            className="min-h-0 flex-1 border-0"
        />
    );
}

function AdminChatEmptySelection() {
    const { t } = useTranslation();

    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-14 text-center">
            <MessageSquare className="size-7 text-gold2" aria-hidden="true" />
            <p className="font-serif text-xl text-cream">
                {t('Selecteer een sessie')}
            </p>
            <p className="max-w-sm font-sans text-[12px] text-sand">
                {t(
                    'Kies links een sessie om de chat te openen. Bij een open sessie kunt u berichten versturen.',
                )}
            </p>
        </div>
    );
}
