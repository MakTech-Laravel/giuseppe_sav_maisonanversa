import { Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { SessionPlayer } from '@/types/session';

type ChatMembersButtonProps = {
    players: SessionPlayer[];
    className?: string;
};

/**
 * Chat header control: opens a list of session players with avatar and name.
 */
export function ChatMembersButton({
    players,
    className,
}: ChatMembersButtonProps) {
    const { t } = useTranslation();

    return (
        <Popover>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    className={cn(
                        'inline-flex h-8 shrink-0 items-center gap-2 border border-gold/30 bg-transparent px-3 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:bg-gold/10 hover:text-gold',
                        className,
                    )}
                    aria-label={t('Leden')}
                >
                    <Users className="size-3.5" aria-hidden="true" />
                    {t('Leden')}
                    <span className="tracking-normal text-sand normal-case tabular-nums">
                        ({players.length})
                    </span>
                </button>
            </PopoverTrigger>
            <PopoverContent
                align="end"
                className="dark w-72 overflow-hidden rounded-none border-gold/40 bg-choc p-0 text-cream shadow-xl"
            >
                <div className="border-b border-gold/25 px-3 py-2.5">
                    <p className="font-sans text-[10px] font-medium tracking-[0.2em] text-gold uppercase">
                        {t('Leden')}
                    </p>
                </div>
                {players.length === 0 ? (
                    <p className="px-3 py-6 text-center font-sans text-[12px] text-sand">
                        {t('Nog geen spelers')}
                    </p>
                ) : (
                    <ul className="max-h-72 overflow-y-auto py-1">
                        {players.map((player) => (
                            <li
                                key={player.id}
                                className="flex items-center gap-3 px-3 py-2"
                            >
                                <Monogram
                                    size="sm"
                                    emphasis={player.is_host}
                                    initials={player.initials}
                                    title={player.name}
                                >
                                    {player.avatar_url ? (
                                        <img
                                            src={player.avatar_url}
                                            alt={player.name}
                                            className="size-full object-cover"
                                        />
                                    ) : undefined}
                                </Monogram>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate font-sans text-[13px] text-cream">
                                        {player.name}
                                    </span>
                                    {player.is_host ? (
                                        <span className="font-sans text-[9px] tracking-[0.16em] text-gold uppercase">
                                            {t('Host')}
                                        </span>
                                    ) : null}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </PopoverContent>
        </Popover>
    );
}
