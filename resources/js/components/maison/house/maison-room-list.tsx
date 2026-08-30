import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { useLocale } from '@/hooks/use-locale';
import type { MaisonPage } from '@/lib/maison-navigation';
import { foundingProductUrl } from '@/lib/maison-navigation';

type Room = {
    /** Omitted for the Heritage No.001 room, which links to the founding product instead. */
    to?: MaisonPage;
    num: string;
    name: string;
    /** When set, the name is left alone — French room titles stay as brand. */
    brand?: true;
    sub: string;
    /** When set, the subtitle is a brand term rather than a dictionary key. */
    brandSub?: true;
};

type Floor = {
    label: string;
    rooms: readonly Room[];
};

const FLOORS: readonly Floor[] = [
    {
        label: 'Rez-de-chaussée',
        rooms: [
            {
                to: 'home',
                num: '01',
                name: 'Salon',
                brand: true,
                sub: 'Onthaal · Home',
            },
            {
                num: '02',
                name: 'Galerie',
                sub: 'Heritage No.001',
                brandSub: true,
            },
            { to: 'contact', num: '03', name: 'Bureau', sub: 'Contact' },
        ],
    },
    {
        label: 'Premier étage',
        rooms: [
            { to: 'story', num: '04', name: 'Atelier', sub: 'Ons Verhaal' },
            {
                to: 'community',
                num: '05',
                name: 'Salle Commune',
                brand: true,
                sub: 'Community',
                brandSub: true,
            },
        ],
    },
    {
        label: 'Deuxième étage',
        rooms: [
            {
                to: 'circle',
                num: '06',
                name: 'Le Cercle',
                brand: true,
                sub: 'Founding Circle',
                brandSub: true,
            },
            {
                to: 'journal',
                num: '07',
                name: 'Bibliotheek',
                sub: 'Journal',
                brandSub: true,
            },
        ],
    },
    {
        label: 'Grenier',
        rooms: [
            {
                to: 'corner',
                num: '08',
                name: 'Club Corner',
                brand: true,
                sub: 'De speelkamer',
            },
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
                                    {room.brand ? room.name : t(room.name)}
                                </span>
                                <span className="font-sans text-[9px] tracking-[0.2em] text-sand uppercase">
                                    {room.brandSub ? room.sub : t(room.sub)}
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
