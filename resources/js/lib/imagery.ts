/**
 * Every photograph the design calls for, at the exact paths the prototype
 * documents. None of them exist yet, so each renders as a brand-palette
 * placeholder until the real file is copied into `public/images` — no code
 * change required, because the server reports what is on disk.
 *
 * `ratio` is the asset's natural shape, used when it is rendered standalone.
 * Call sites inside a fixed-ratio container pass their own ratio instead.
 */
export type ImageAsset = {
    /** Relative to the public root, e.g. `images/rooms/room-entrance.png`. */
    path: string;
    /** CSS `aspect-ratio` value. */
    ratio: string;
    /** Dutch caption, shown on the placeholder and used as the default alt. */
    label: string;
    /** Brand-palette gradient standing in for the photograph. */
    gradient: string;
    /**
     * Whether that gradient is light or dark, so the placeholder caption keeps
     * its contrast. Most of the photography is dark; the facade drawings are
     * line art on a pale ground.
     */
    ground?: 'light';
};

const CHOC = '#291c18';
const CHOC2 = '#352722';
const CHOC3 = '#41332d';
const GOLD = '#8d705a';
const GOLD2 = '#745a48';
const CREAM = '#f3ebe3';
const SAND = '#b8a898';

export const IMAGE_ASSETS = {
    /* The six intro room panels, each given its own tone so the slideshow
     * still reads as six distinct rooms before the photography arrives. */
    'room-entrance': {
        path: 'images/rooms/room-entrance.png',
        ratio: '16 / 9',
        label: 'Entreehal',
        gradient: `linear-gradient(160deg, ${CHOC3} 0%, ${CHOC} 100%)`,
    },
    'room-library': {
        path: 'images/rooms/room-library.png',
        ratio: '16 / 9',
        label: 'Bibliotheek',
        gradient: `linear-gradient(200deg, ${CHOC2} 0%, ${CHOC} 100%)`,
    },
    'room-atelier': {
        path: 'images/rooms/room-atelier.png',
        ratio: '16 / 9',
        label: 'Atelier',
        gradient: `linear-gradient(135deg, ${CHOC3} 0%, ${GOLD2} 55%, ${CHOC} 100%)`,
    },
    'room-dressing': {
        path: 'images/rooms/room-dressing.png',
        ratio: '16 / 9',
        label: 'Kleedkamer',
        gradient: `linear-gradient(120deg, ${CHOC2} 0%, ${CHOC3} 100%)`,
    },
    'room-coffee': {
        path: 'images/rooms/room-coffee.png',
        ratio: '16 / 9',
        label: 'Koffiekamer',
        gradient: `linear-gradient(175deg, ${CHOC3} 0%, ${CHOC2} 60%, ${CHOC} 100%)`,
    },
    'room-courtyard': {
        path: 'images/rooms/room-courtyard.png',
        ratio: '16 / 9',
        label: 'Binnenhof',
        gradient: `linear-gradient(210deg, ${GOLD2} 0%, ${CHOC2} 60%, ${CHOC} 100%)`,
    },

    /* Brand imagery. The two facade drawings are line art on a light ground,
     * so their placeholders stay light where the photographs are dark. */
    'hero-mansion': {
        path: 'images/brand/hero-mansion.png',
        ratio: '16 / 9',
        label: 'Herenhuis',
        gradient: `linear-gradient(180deg, ${CHOC2} 0%, ${CHOC} 100%)`,
    },
    'maison-facade': {
        path: 'images/brand/maison-facade.png',
        ratio: '3 / 4',
        label: 'Geveltekening',
        gradient: `linear-gradient(165deg, ${SAND} 0%, ${GOLD} 100%)`,
        ground: 'light',
    },
    'maison-facade-house': {
        path: 'images/brand/maison-facade-house.png',
        ratio: '3 / 2',
        label: 'Huistekening',
        gradient: `linear-gradient(165deg, ${CREAM} 0%, ${SAND} 100%)`,
        ground: 'light',
    },
    'antwerp-cityscape': {
        path: 'images/brand/antwerp-cityscape.png',
        ratio: '3 / 2',
        label: 'Antwerpen',
        gradient: `linear-gradient(200deg, ${GOLD} 0%, ${CHOC2} 65%, ${CHOC} 100%)`,
    },

    /* The product photography. */
    'heritage-001-front': {
        path: 'images/product/heritage-001-front.png',
        ratio: '4 / 5',
        label: 'Heritage No.001',
        gradient: `linear-gradient(140deg, ${CHOC2} 0%, ${CHOC} 100%)`,
    },
    'heritage-001-detail-gravure': {
        path: 'images/product/heritage-001-detail-gravure.png',
        ratio: '1 / 1',
        label: 'Gravure',
        gradient: `linear-gradient(140deg, ${CHOC3} 0%, ${GOLD2} 100%)`,
    },
    'heritage-001-lifestyle-court': {
        path: 'images/product/heritage-001-lifestyle-court.png',
        ratio: '3 / 2',
        label: 'Op het veld',
        gradient: `linear-gradient(155deg, ${GOLD2} 0%, ${CHOC2} 70%, ${CHOC} 100%)`,
    },
    'atelier-workshop': {
        path: 'images/product/atelier-workshop.png',
        ratio: '3 / 2',
        label: 'Atelier',
        gradient: `linear-gradient(145deg, ${CHOC3} 0%, ${CHOC} 100%)`,
    },

    /* The etching band that runs full-width above the footer. */
    'antwerp-ets-band': {
        path: 'images/editorial/antwerp-ets-band.png',
        ratio: '32 / 9',
        label: 'Antwerpen, ets',
        gradient: `linear-gradient(90deg, ${CHOC} 0%, ${CHOC3} 50%, ${CHOC} 100%)`,
    },

    /* The wordmark and emblem. */
    'logo-icon': {
        path: 'images/logos/logo-icon.jpg',
        ratio: '1 / 1',
        label: 'Maison Anversa',
        gradient: `linear-gradient(140deg, ${CHOC2} 0%, ${CHOC} 100%)`,
    },
    'logo-emblem': {
        path: 'images/logos/logo-emblem.jpg',
        ratio: '20 / 11',
        label: 'Maison Anversa',
        gradient: `linear-gradient(140deg, ${CHOC2} 0%, ${CHOC} 100%)`,
    },
} as const satisfies Record<string, ImageAsset>;

export type ImageAssetName = keyof typeof IMAGE_ASSETS;

export function imageAsset(name: ImageAssetName): ImageAsset {
    return IMAGE_ASSETS[name];
}
