import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { SessionChat } from '@/components/maison/community/sessions/chat/session-chat';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import { isSessionChatOpen } from '@/lib/session-chat';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import * as sessionRoutes from '@/routes/community/sessions';
import type { SessionCard, SessionPlayer } from '@/types/session';
import type { ChatMessage } from '@/types/session-chat';

type SessionChatPageProps = {
    session: SessionCard;
    viewer: SessionPlayer;
    messages: ChatMessage[];
};

export default function SessionChatPage({
    session,
    viewer,
    messages: initialMessages,
}: SessionChatPageProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [messages, setMessages] = useState<ChatMessage[]>(initialMessages);
    const localUrls = useRef<string[]>([]);

    useEffect(
        () => () =>
            localUrls.current.forEach((url) => URL.revokeObjectURL(url)),
        [],
    );

    const canSend = isSessionChatOpen(session);
    const clubName = session.club?.name ?? t('Sessie');

    /** Messages only live in this page until chat storage exists. */
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

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={`${t(session.sport_label)}${session.club ? ` · ${session.club.city}` : ''}`}
                title={clubName}
                subtitle={t('Sessiechat')}
            />

            <CommunityTabs />

            <div className="bg-cream py-12">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <Link
                        href={
                            sessionRoutes.show({
                                locale,
                                communitySession: Number(session.id),
                            }).url
                        }
                        className="mb-6 inline-flex items-center gap-2 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:text-gold2"
                    >
                        <ArrowLeft className="size-3.5" aria-hidden="true" />
                        {t('Terug naar sessie')}
                    </Link>

                    <SessionChat
                        messages={messages}
                        currentUserId={viewer.id}
                        canSend={canSend}
                        onSend={handleSend}
                        title={clubName}
                        subtitle={`${formatSessionDateLong(session.starts_at, locale)} · ${formatSessionTime(session.starts_at, locale)}`}
                        headerAside={
                            <PlayerSlots
                                players={session.players}
                                openSlots={0}
                            />
                        }
                        closedMessage={
                            session.is_cancelled
                                ? t(
                                      'Deze sessie is geannuleerd. De chat is gesloten.',
                                  )
                                : t(
                                      'Deze sessie is afgelopen. De chat is gesloten.',
                                  )
                        }
                    />
                </Wrap>
            </div>
        </>
    );
}
