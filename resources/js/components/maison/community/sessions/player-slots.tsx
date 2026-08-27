import { X } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';
import { cn } from '@/lib/utils';
import type { SessionPlayer } from '@/types/session';

type PlayerSlotsProps = {
    players: SessionPlayer[];
    openSlots: number;
    size?: 'md' | 'lg';
    /** Renders names and roles beneath each avatar, as on the detail page. */
    withLabels?: boolean;
    onRemove?: (player: SessionPlayer) => void;
};

/**
 * The avatar row from the mockup: filled slots first, then dashed placeholders
 * for however many seats are still open.
 */
export function PlayerSlots({
    players,
    openSlots,
    size = 'md',
    withLabels = false,
    onRemove,
}: PlayerSlotsProps) {
    const { t } = useTranslation();

    return (
        <div
            className={cn(
                'flex flex-wrap items-start gap-3',
                withLabels && 'gap-5',
            )}
        >
            {players.map((player) => (
                <div
                    key={player.id}
                    className="group relative flex w-14 flex-col items-center gap-1.5"
                >
                    <Monogram
                        size={size}
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

                    {withLabels && (
                        <>
                            <span className="max-w-14 truncate font-sans text-[10px] tracking-[0.08em] text-choc">
                                {player.name}
                            </span>
                            <span className="font-sans text-[9px] tracking-[0.16em] text-stone uppercase">
                                {player.is_host ? t('Host') : t('Aangesloten')}
                            </span>
                        </>
                    )}

                    {onRemove && !player.is_host && (
                        <button
                            type="button"
                            onClick={() => onRemove(player)}
                            aria-label={t('Speler verwijderen')}
                            className="absolute -top-1 -right-1 flex size-5 cursor-pointer items-center justify-center rounded-full border border-gold/30 bg-cream text-choc opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                        >
                            <X className="size-3" />
                        </button>
                    )}
                </div>
            ))}

            {Array.from({ length: Math.max(0, openSlots) }).map((_, index) => (
                <div
                    key={`open-${index}`}
                    className="flex w-14 flex-col items-center gap-1.5"
                >
                    <Monogram
                        size={size}
                        initials="+"
                        className="border-dashed bg-gold/5 text-gold/40"
                    />

                    {withLabels && (
                        <span className="font-sans text-[9px] tracking-[0.16em] text-stone uppercase">
                            {t('Vrij')}
                        </span>
                    )}
                </div>
            ))}
        </div>
    );
}
