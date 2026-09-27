import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { cn } from '@/lib/utils';

type Place = {
    number: string;
    sequence: number;
    state: 'inscribed' | 'available' | 'archive';
    label_key: string | null;
    name: string | null;
    year: string | null;
    italic: boolean;
    you: boolean;
};

type Filter = 'all' | 'inscribed' | 'available';

function placeText(place: Place, t: (key: string) => string): string {
    if (place.name) {
        return place.name;
    }

    return t(place.label_key ?? 'Beschikbaar');
}

export default function OfficialRegister({ places }: { places: Place[] }) {
    const { t } = useTranslation();
    const { locale, auth, foundingRegister } = usePage().props;
    const { openAuth } = useShellActions();
    const [filter, setFilter] = useState<Filter>('all');
    const [showAll, setShowAll] = useState(false);
    const columns = [places.slice(0, 25), places.slice(25, 50), places.slice(50, 75), places.slice(75, 100)];

    function matches(place: Place): boolean {
        if (filter === 'all') {
            return true;
        }

        if (filter === 'inscribed') {
            return place.state === 'inscribed';
        }

        return place.state === 'available';
    }

    function manageListing() {
        if (!auth?.user) {
            openAuth('login');

            return;
        }

        window.location.assign(`/${locale}/member/register-listing`);
    }

    return (
        <>
            <MaisonSeoHead
                noIndex
                title={t('Het officiële register')}
                description={t(
                    'Honderd plaatsen. Eén eerste hoofdstuk. Elk lid van de Founding Circle wordt ingeschreven in de volgorde waarin zij het huis vonden.',
                )}
            />

            <section className="bg-cream px-6 pt-16 pb-20 text-choc md:px-10">
                <div className="mx-auto max-w-6xl text-center">
                    <p className="font-sans text-[10px] tracking-[0.28em] text-gold2 uppercase">
                        {t('De Founding Circle')}
                    </p>
                    <h1 className="mt-4 font-serif text-[clamp(40px,6vw,72px)] leading-none font-medium">
                        {t('Het officiële register')}
                    </h1>
                    <span className="mx-auto mt-5 block h-px w-16 bg-gold" />
                    <p className="mx-auto mt-6 max-w-xl text-[15px] leading-[1.7] text-choc3">
                        {t(
                            'Honderd plaatsen. Eén eerste hoofdstuk. Elk lid van de Founding Circle wordt ingeschreven in de volgorde waarin zij het huis vonden, en blijft deel van de geschiedenis zolang het huis bestaat.',
                        )}
                    </p>

                    <dl className="mx-auto mt-12 grid max-w-3xl grid-cols-3 gap-6">
                        <div>
                            <dt className="font-sans text-[9px] tracking-[0.18em] text-gold2 uppercase">
                                {t('Ingeschreven')}
                            </dt>
                            <dd className="mt-2 font-serif text-4xl">
                                {foundingRegister.inscribed_count}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-sans text-[9px] tracking-[0.18em] text-gold2 uppercase">
                                {t('Plaatsen resterend')}
                            </dt>
                            <dd className="mt-2 font-serif text-4xl">
                                {foundingRegister.remaining_count}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-sans text-[9px] tracking-[0.18em] text-gold2 uppercase">
                                {t('Plaatsen in totaal')}
                            </dt>
                            <dd className="mt-2 font-serif text-4xl">
                                {foundingRegister.places_total}
                            </dd>
                        </div>
                    </dl>

                    <div className="mt-10 flex flex-col gap-2 sm:flex-row sm:justify-center">
                        {(
                            [
                                ['all', t('Alle 100')],
                                ['inscribed', t('Ingeschreven')],
                                ['available', t('Beschikbaar')],
                            ] as const
                        ).map(([value, label]) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => setFilter(value)}
                                className={cn(
                                    'min-h-11 border px-4 font-sans text-[10px] tracking-[0.16em] uppercase',
                                    filter === value
                                        ? 'border-choc bg-choc text-cream'
                                        : 'border-gold/30 bg-transparent text-choc',
                                )}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    <ul className="mt-4 hidden items-center justify-end gap-4 font-sans text-[10px] tracking-[0.12em] text-choc3 uppercase md:flex">
                        <li>{t('Ingeschreven met naam')}</li>
                        <li className="italic">{t('Privélid')}</li>
                        <li>{t('Uw vermelding')}</li>
                    </ul>

                    <div className="mt-6 hidden gap-8 text-left md:grid md:grid-cols-4">
                        {columns.map((column) => (
                            <ol key={column[0]?.number} className="space-y-0">
                                {column.map((place) => (
                                    <PlaceRow
                                        key={place.number}
                                        place={place}
                                        dimmed={!matches(place)}
                                        label={placeText(place, t)}
                                    />
                                ))}
                            </ol>
                        ))}
                    </div>

                    <ol className="mt-6 text-left md:hidden">
                        {(showAll ? places : places.slice(0, 20))
                            .filter((place) => matches(place))
                            .map((place) => (
                                <PlaceRow
                                    key={place.number}
                                    place={place}
                                    label={placeText(place, t)}
                                />
                            ))}
                    </ol>

                    {!showAll && (
                        <button
                            type="button"
                            onClick={() => setShowAll(true)}
                            className="mt-6 min-h-11 w-full border border-gold/40 font-sans text-[10px] tracking-[0.16em] text-choc uppercase md:hidden"
                        >
                            {t('Toon alle 100 plaatsen')}
                        </button>
                    )}

                    <div className="mt-14 flex flex-col items-start gap-6 border border-gold/20 bg-cream2 p-8 text-left md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 className="font-serif text-3xl">
                                {t('Uw naam, uw keuze.')}
                            </h2>
                            <p className="mt-3 max-w-xl text-sm leading-relaxed text-choc3">
                                {t(
                                    'Elk lid staat in het officiële archief. Of uw volledige naam, uw voornaam en initiaal, of alleen uw nummer publiek verschijnt, kiest u zelf.',
                                )}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={manageListing}
                            className="min-h-11 shrink-0 bg-choc px-5 font-sans text-[10px] tracking-[0.16em] text-cream uppercase"
                        >
                            {t('Beheer mijn vermelding')}
                        </button>
                    </div>
                </div>
            </section>
        </>
    );
}

function PlaceRow({
    place,
    label,
    dimmed = false,
}: {
    place: Place;
    label: string;
    dimmed?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <li
            className={cn(
                'flex min-h-11 items-baseline gap-3 border-b border-gold/15 py-2 font-serif text-[15px]',
                dimmed && 'opacity-30',
                place.you && 'bg-gold/20 px-2',
            )}
        >
            <span className="w-8 shrink-0 text-gold2">{place.number}</span>
            <span className={cn('min-w-0 flex-1 truncate', place.italic && 'italic')}>
                {label}
            </span>
            {place.you ? (
                <span className="font-sans text-[9px] tracking-[0.14em] text-gold2 uppercase">
                    {t('U')}
                </span>
            ) : (
                place.year && (
                    <span className="font-sans text-[11px] text-choc3">{place.year}</span>
                )
            )}
        </li>
    );
}
