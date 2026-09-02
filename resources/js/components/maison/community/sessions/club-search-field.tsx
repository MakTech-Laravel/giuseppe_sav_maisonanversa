import { usePage } from '@inertiajs/react';
import { Check, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { ClubSubmitDialog } from '@/components/maison/community/sessions/club-submit-dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import * as clubRoutes from '@/routes/community/clubs';
import type { ClubCard } from '@/types/session';

type ClubSearchFieldProps = {
    sport: string;
    selected: ClubCard | null;
    onSelect: (club: ClubCard | null) => void;
    /** Shown before the member types two letters. */
    suggestions: ClubCard[];
    error?: string;
};

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

export function ClubSearchField({
    sport,
    selected,
    onSelect,
    suggestions,
    error,
}: ClubSearchFieldProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<ClubCard[]>([]);
    const [searching, setSearching] = useState(false);
    const [searched, setSearched] = useState(false);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        const query = term.trim();

        if (query.length < MIN_QUERY_LENGTH) {
            abortRef.current?.abort();

            return;
        }

        const controller = new AbortController();
        abortRef.current?.abort();
        abortRef.current = controller;

        const timer = window.setTimeout(() => {
            setSearching(true);

            const url = clubRoutes.search.url(locale, {
                query: { q: query, sport },
            });

            fetch(url, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((payload: { clubs: ClubCard[] }) => {
                    setResults(payload.clubs);
                    setSearched(true);
                })
                .catch(() => {
                    // An aborted request is the expected path while typing.
                })
                .finally(() => setSearching(false));
        }, DEBOUNCE_MS);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [term, sport, locale]);

    if (selected) {
        return (
            <div className="space-y-3">
                <SelectedClubCard club={selected} />
                <button
                    type="button"
                    onClick={() => onSelect(null)}
                    className="cursor-pointer font-sans text-[10px] tracking-[0.16em] text-stone uppercase underline underline-offset-4 hover:text-choc"
                >
                    {t('Andere club kiezen')}
                </button>
            </div>
        );
    }

    const query = term.trim();
    const waitingForQuery = query.length > 0 && query.length < MIN_QUERY_LENGTH;
    const belowMinLength = query.length < MIN_QUERY_LENGTH;
    const showEmptyState =
        !belowMinLength && searched && !searching && results.length === 0;
    const clubs = waitingForQuery ? [] : belowMinLength ? suggestions : results;

    return (
        <div className="space-y-3">
            <div className="relative">
                <Search
                    className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-stone"
                    aria-hidden="true"
                />
                <input
                    type="search"
                    value={term}
                    onChange={(event) => setTerm(event.target.value)}
                    placeholder={t('Zoek club, stad of postcode...')}
                    aria-label={t('Zoek club, stad of postcode...')}
                    className={cn(
                        'w-full border border-gold/20 bg-cream py-3.5 pr-4 pl-11 font-serif text-base text-choc transition-colors outline-none focus:border-gold2',
                        error && 'border-red-700/50',
                    )}
                />
            </div>

            {error && (
                <p className="font-sans text-[11px] text-red-800">{error}</p>
            )}

            {waitingForQuery && (
                <p className="font-sans text-[11px] text-stone">
                    {t('Typ minstens 2 letters om te zoeken.')}
                </p>
            )}

            {clubs.length > 0 && (
                <ScrollArea className="h-64 border border-gold/15">
                    <div className="space-y-2 p-2">
                        {clubs.map((club) => (
                            <button
                                key={club.id}
                                type="button"
                                onClick={() => onSelect(club)}
                                className="flex w-full cursor-pointer items-center gap-4 border border-gold/15 bg-cream p-3 text-left transition-colors hover:border-gold/40"
                            >
                                <ClubThumbnail club={club} />

                                <span className="min-w-0 flex-1">
                                    <span className="block truncate font-serif text-base text-choc">
                                        {club.name}
                                    </span>
                                    <span className="block truncate font-sans text-[11px] text-stone">
                                        {club.address}
                                    </span>
                                </span>

                                {club.is_partner && <PartnerBadge />}
                            </button>
                        ))}
                    </div>
                </ScrollArea>
            )}

            {showEmptyState && (
                <p className="font-sans text-[11px] text-stone">
                    {t('Geen club gevonden voor deze zoekopdracht.')}
                </p>
            )}

            <ClubSubmitDialog defaultName={term} defaultSport={sport} />
        </div>
    );
}

function SelectedClubCard({ club }: { club: ClubCard }) {
    return (
        <div className="flex items-center gap-4 border border-gold/25 bg-cream2 p-3">
            <ClubThumbnail club={club} />

            <div className="min-w-0 flex-1">
                <p className="truncate font-serif text-base text-choc">
                    {club.name}
                </p>
                <p className="truncate font-sans text-[11px] text-stone">
                    {club.address}
                </p>
            </div>

            {club.is_partner ? (
                <PartnerBadge />
            ) : (
                <Check className="size-4 text-gold2" aria-hidden="true" />
            )}
        </div>
    );
}

function ClubThumbnail({ club }: { club: ClubCard }) {
    if (club.image_url) {
        return (
            <img
                src={club.image_url}
                alt=""
                className="size-14 shrink-0 object-cover"
            />
        );
    }

    return (
        <div
            aria-hidden="true"
            className="flex size-14 shrink-0 items-center justify-center bg-choc2 font-serif text-lg text-gold"
        >
            {club.name.slice(0, 1).toUpperCase()}
        </div>
    );
}

function PartnerBadge() {
    const { t } = useTranslation();

    return (
        <span className="shrink-0 border border-gold/25 bg-gold/8 px-2.5 py-1 font-sans text-[9px] tracking-[0.14em] text-gold2 uppercase">
            {t('Partnerclub')}
        </span>
    );
}
