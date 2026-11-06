import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { roomText } from '@/components/maison/opening/opening-data';
import { useLocale } from '@/hooks/use-locale';
import type { MaisonPage } from '@/lib/maison-navigation';
import { foundingProductUrl } from '@/lib/maison-navigation';

type Room = {
    /** Omitted for the Heritage Nº 001 room, which links to the founding product instead. */
    to?: MaisonPage;
    num: string;
    /** Name and label come from the opening's room texts, never from the dictionary. */
    numeral: string;
};

type Floor = {
    label: string;
    rooms: readonly Room[];
};

const FLOORS: readonly Floor[] = [
    {
        label: 'Rez-de-chaussée',
        rooms: [
            { to: 'home', num: '01', numeral: 'I' },
            { num: '02', numeral: 'IV' },
            { to: 'contact', num: '03', numeral: 'II' },
        ],
    },
    {
        label: 'Premier étage',
        rooms: [
            { to: 'story', num: '04', numeral: 'VII' },
            { to: 'community', num: '05', numeral: 'V' },
        ],
    },
    {
        label: 'Deuxième étage',
        rooms: [
            { to: 'circle', num: '06', numeral: 'III' },
            { to: 'journal', num: '07', numeral: 'VI' },
        ],
    },
    {
        label: 'Grenier',
        rooms: [
            { to: 'corner', num: '08', numeral: 'VIII' },
        ],
    },
] as const;

/**
 * The eight rooms as a list. Kept as a fallback — the elevation drawing is
 * now the navigation at every width. Same destinations as the SVG.
 */
export function MaisonRoomList() {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <div className="mx-auto max-w-140 min-[641px]:hidden">
            {FLOORS.map((floor) => (
                <div key={floor.label}>
                    <p className="mt-6.5 mb-1.5 font-sans text-[8px] tracking-[0.3em] text-gold/70 uppercase first:mt-0">
                        {t(floor.label)}
                    </p>
                    {floor.rooms.map((room) => {
                        const body = (
                            <>
                                <span className="w-7.5 font-serif text-[13px] tracking-[0.1em] text-gold">
                                    {room.num}
                                </span>
                                <span className="flex-1 font-serif text-[21px] text-cream transition-colors hover:text-gold">
                                    {roomText(locale, room.numeral).name}
                                </span>
                                <span className="font-sans text-[9px] tracking-[0.2em] text-sand uppercase">
                                    {roomText(locale, room.numeral).subtitle}
                                </span>
                                <span
                                    aria-hidden="true"
                                    className="text-base text-gold"
                                >
                                    →
                                </span>
                            </>
                        );
                        const className =
                            'flex items-center gap-4.5 border-b border-gold/14 py-5.5 transition-[padding] active:pl-3.5';

                        return room.to ? (
                            <MaisonLink
                                key={room.num}
                                to={room.to}
                                className={className}
                            >
                                {body}
                            </MaisonLink>
                        ) : (
                            <MaisonLink
                                key={room.num}
                                href={foundingProductUrl(locale)}
                                className={className}
                            >
                                {body}
                            </MaisonLink>
                        );
                    })}
                </div>
            ))}
        </div>
    );
}

export { FLOORS };
