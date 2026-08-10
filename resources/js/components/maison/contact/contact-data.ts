import type { LucideIcon } from 'lucide-react';
import {
    CalendarDays,
    Heart,
    HelpCircle,
    Mail,
    MapPin,
    MessageCircle,
    MessageSquare,
    Package,
    Phone,
    Video,
} from 'lucide-react';

export type BureauPanelId =
    | 'bestel'
    | 'care'
    | 'afspraak'
    | 'concierge'
    | 'boutique'
    | 'faq'
    | 'feedback';

type BureauLinkBubble = {
    kind: 'link';
    icon: LucideIcon;
    name: string;
    sub: string;
    href: string;
};

type BureauPanelBubble = {
    kind: 'panel';
    icon: LucideIcon;
    name: string;
    sub: string;
    panel: BureauPanelId;
};

export type BureauBubble = BureauLinkBubble | BureauPanelBubble;

export const BUREAU_BUBBLES: readonly BureauBubble[] = [
    {
        kind: 'link',
        icon: MessageCircle,
        name: 'WhatsApp ons',
        sub: 'Snelste antwoord',
        href: 'https://wa.me/32400000000',
    },
    {
        kind: 'link',
        icon: Phone,
        name: 'Bel ons',
        sub: 'Ma–Vr · 10–18u',
        href: 'tel:+32400000000',
    },
    {
        kind: 'link',
        icon: Mail,
        name: 'E-mail ons',
        sub: 'hello@maisonanversa.com',
        href: 'mailto:hello@maisonanversa.com',
    },
    {
        kind: 'panel',
        icon: Package,
        name: 'Bestelstatus',
        sub: 'Volg uw bestelling',
        panel: 'bestel',
    },
    {
        kind: 'panel',
        icon: Heart,
        name: 'Maison Care',
        sub: 'Onderhoud & garantie',
        panel: 'care',
    },
    {
        kind: 'panel',
        icon: CalendarDays,
        name: 'Boek een afspraak',
        sub: 'Privé in ons atelier',
        panel: 'afspraak',
    },
    {
        kind: 'panel',
        icon: Video,
        name: 'Privé consult',
        sub: 'Video met een specialist',
        panel: 'concierge',
    },
    {
        kind: 'panel',
        icon: MapPin,
        name: 'Vind uw Boutique',
        sub: 'Antwerpen',
        panel: 'boutique',
    },
    {
        kind: 'panel',
        icon: HelpCircle,
        name: 'Veelgestelde vragen',
        sub: 'FAQ',
        panel: 'faq',
    },
    {
        kind: 'panel',
        icon: MessageSquare,
        name: 'Uw mening',
        sub: 'Feedback',
        panel: 'feedback',
    },
] as const;

export const BUREAU_PANEL_IDS: readonly BureauPanelId[] = [
    'bestel',
    'care',
    'afspraak',
    'concierge',
    'boutique',
    'faq',
    'feedback',
] as const;

export const CONTACT_FAQ = [
    {
        question: 'Wanneer levert Heritage No.001?',
        answer: 'Levering is gepland in Q1 2027. De Founding Edition levert in volgorde van reservering — hoe vroeger u reserveert, hoe lager uw nummer.',
    },
    {
        question: 'Hoe wordt mijn racket geleverd?',
        answer: 'In een handgemaakte omslag, vergezeld van een certificaat van echtheid en uw genummerd editienummer.',
    },
    {
        question: 'Kan ik mijn racket retourneren?',
        answer: 'Retourneren binnen 14 dagen na levering, mits ongebruikt. Het definitieve retourbeleid wordt vastgelegd bij lancering.',
    },
    {
        question: 'Is de prijs inclusief verzending?',
        answer: 'Verzending binnen Europa is inbegrepen. Buiten Europa op aanvraag.',
    },
    {
        question: 'Hoe onderhoud ik het leder?',
        answer: 'Behandel het leder tweemaal per jaar met een neutrale lederbalsem. Vermijd langdurig vocht en direct zonlicht.',
    },
    {
        question: 'Kan ik het racket personaliseren?',
        answer: 'De Founding Edition is genummerd. Verdere personalisatie volgt in latere edities.',
    },
] as const;

export const BOUTIQUE_MAP_SRC =
    'https://www.openstreetmap.org/export/embed.html?bbox=4.388%2C51.206%2C4.428%2C51.234&layer=mapnik&marker=51.220%2C4.404';
