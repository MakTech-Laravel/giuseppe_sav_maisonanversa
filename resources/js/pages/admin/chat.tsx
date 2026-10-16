import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, MessagesSquare } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { SessionChat } from '@/components/maison/community/sessions/chat/session-chat';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { ChatSessionList } from '@/components/member/chat/chat-session-list';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import * as adminChat from '@/routes/admin/chat';
import type { SessionCard, SessionPlayer } from '@/types/session';
import type { ChatMessage } from '@/types/session-chat';

type AdminChatPageProps = {
    sessions: SessionCard[];
    activeSession: SessionCard | null;
    viewer: SessionPlayer;
    messages: ChatMessage[];
};

export default function AdminChatPage({
    sessions,
    activeSession,
    viewer,
    messages,
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
                        'Bekijk sessiechats. Beheerders kunnen berichten lezen maar niet versturen.',
                    )}
                    icon={MessagesSquare}
                />

                <div className="flex min-h-120 flex-1 overflow-hidden border border-gold/25 bg-choc3 text-cream">
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

                                <SessionChat
                                    messages={messages}
                                    currentUserId={viewer.id}
                                    canSend={false}
                                    title={
                                        activeSession.club?.name ?? t('Sessie')
                                    }
                                    subtitle={`${formatSessionDateLong(activeSession.starts_at, locale)} · ${formatSessionTime(activeSession.starts_at, locale)}`}
                                    headerAside={
                                        <PlayerSlots
                                            players={activeSession.players}
                                            openSlots={0}
                                        />
                                    }
                                    closedMessage={t(
                                        'Alleen-lezen. Beheerders kunnen geen berichten versturen.',
                                    )}
                                    className="min-h-0 flex-1 border-0"
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
                    'Kies links een sessie om de chat te openen. U kunt berichten lezen maar niet versturen.',
                )}
            </p>
        </div>
    );
}
