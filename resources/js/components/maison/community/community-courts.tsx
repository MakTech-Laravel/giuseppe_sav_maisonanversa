import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    COURT_CARDS,
    COURT_PINS,
} from '@/components/maison/community/community-data';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';

/**
 * Club Corners: map first, then the full club list in normal document flow —
 * no nested side-column scroll that disappears halfway down the page.
 */
export function CommunityCourts() {
    const { t } = useTranslation();
    const [highlightedCourt, setHighlightedCourt] = useState<string | null>(
        null,
    );

    return (
        <Wrap className="space-y-10 px-6 py-12 md:px-10 lg:px-20">
            <div className="relative aspect-16/10 w-full overflow-hidden border border-gold/10 bg-linear-to-br from-[#1A2E22] to-[#0F1A14] lg:aspect-21/9">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(31,61,46,0.35)_1px,transparent_1px),linear-gradient(90deg,rgba(31,61,46,0.35)_1px,transparent_1px)] bg-[size:40px_40px]"
                />

                {COURT_PINS.map((pin) => (
                    <button
                        key={pin.id}
                        type="button"
                        onClick={() => {
                            setHighlightedCourt(pin.id);
                            document
                                .getElementById(`court-${pin.id}`)
                                ?.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'nearest',
                                });
                        }}
                        className="absolute z-2 flex cursor-pointer flex-col items-center border-none bg-transparent p-0"
                        style={{ top: pin.top, left: pin.left }}
                    >
                        <span
                            className={cn(
                                'size-3.5 rounded-full border-2 border-choc shadow-[0_0_0_4px_rgba(141,112,90,0.2)] transition-transform hover:scale-130',
                                pin.coming
                                    ? 'bg-stone shadow-[0_0_0_4px_rgba(138,125,114,0.15)]'
                                    : 'bg-gold',
                            )}
                        />
                        <span
                            className={cn(
                                'mt-1 border border-gold/20 bg-choc/85 px-2 py-0.5 font-sans text-[9px] tracking-[0.12em] whitespace-nowrap uppercase',
                                pin.coming ? 'text-stone' : 'text-gold',
                            )}
                        >
                            {t(pin.label)}
                        </span>
                    </button>
                ))}

                <div className="absolute bottom-4 left-5 flex flex-wrap gap-4">
                    <div className="flex items-center gap-1.5 font-sans text-[9px] tracking-[0.12em] text-gold/50 uppercase">
                        <span className="size-2 rounded-full bg-gold" />
                        {t('Club Corner actief')}
                    </div>
                    <div className="flex items-center gap-1.5 font-sans text-[9px] tracking-[0.12em] text-gold/50 uppercase">
                        <span className="size-2 rounded-full bg-stone" />
                        {t('Binnenkort')}
                    </div>
                </div>
            </div>

            <div>
                <h2 className="mb-6 font-serif text-[28px] font-medium text-choc">
                    {t('Club Corners')}
                </h2>

                <div className="grid gap-3 md:grid-cols-2">
                    {COURT_CARDS.map((court) => (
                        <article
                            key={court.id}
                            id={`court-${court.id}`}
                            className={cn(
                                'border bg-cream2 p-6 transition-colors hover:border-gold/35',
                                highlightedCourt === court.id
                                    ? 'border-gold/35'
                                    : 'border-gold/15',
                            )}
                        >
                            <div className="mb-2.5 flex items-start justify-between gap-3">
                                <div className="font-serif text-xl font-medium text-choc">
                                    {t(court.name)}
                                </div>
                                <div
                                    className={cn(
                                        'shrink-0 border px-2.5 py-1 font-sans text-[9px] tracking-[0.15em] uppercase',
                                        court.statusComing
                                            ? 'border-gold/25 text-stone'
                                            : 'border-gold text-gold',
                                    )}
                                >
                                    {t(court.status)}
                                </div>
                            </div>

                            <div className="mb-3 font-sans text-[10px] tracking-[0.1em] text-stone">
                                📍 {t(court.location)}
                            </div>

                            <div className="flex gap-5">
                                {court.stats.map((stat) => (
                                    <div
                                        key={stat.label}
                                        className="text-center"
                                    >
                                        <div className="font-serif text-[22px] font-light text-gold2">
                                            {stat.num}
                                        </div>
                                        <div className="font-sans text-[8px] tracking-[0.15em] text-stone uppercase">
                                            {t(stat.label)}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-3 flex flex-wrap gap-2">
                                {court.tags.map((tag) => (
                                    <span
                                        key={tag}
                                        className={cn(
                                            'border px-2.5 py-0.5 font-sans text-[8px] tracking-[0.15em] uppercase',
                                            court.tagsMuted
                                                ? 'border-gold/10 bg-gold/8 text-stone'
                                                : 'border-gold/15 bg-gold/8 text-gold2',
                                        )}
                                    >
                                        {t(tag)}
                                    </span>
                                ))}
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </Wrap>
    );
}
