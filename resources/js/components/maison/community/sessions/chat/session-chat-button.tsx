import { Link, usePage } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { canOpenSessionChat } from '@/lib/session-chat';
import { cn } from '@/lib/utils';
import adminChat from '@/routes/admin/chat';
import memberChat from '@/routes/member/chat';
import type { SessionCard } from '@/types/session';

type SessionChatButtonProps = {
    session: SessionCard;
    /** Show "Chat" next to the icon instead of the icon alone. */
    withLabel?: boolean;
    className?: string;
};

export function SessionChatButton({
    session,
    withLabel = false,
    className,
}: SessionChatButtonProps) {
    const { t } = useTranslation();
    const { locale, auth } = usePage().props;
    const isAdmin = Boolean(auth?.user?.is_admin);

    if (!canOpenSessionChat(session, { isAdmin })) {
        return null;
    }

    const label = t('Open de sessiechat');
    const href = isAdmin
        ? adminChat.show({
              locale,
              communitySession: Number(session.id),
          }).url
        : memberChat.show({
              locale,
              communitySession: Number(session.id),
          }).url;

    return (
        <Link
            href={href}
            aria-label={withLabel ? undefined : label}
            title={label}
            className={cn(
                'inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 border border-gold/30 bg-transparent font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10',
                withLabel ? 'px-7 py-3.5' : 'size-11.5',
                className,
            )}
        >
            <MessageCircle className="size-4" aria-hidden="true" />
            {withLabel && t('Chat')}
        </Link>
    );
}
