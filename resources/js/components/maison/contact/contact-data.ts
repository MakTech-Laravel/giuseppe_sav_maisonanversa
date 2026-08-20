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

export type SiteShared = {
    phone: string;
    whatsapp: string;
    emailHello: string;
    emailPress: string;
    instagramUrl: string;
    boutiqueLat: number;
    boutiqueLng: number;
    boutiqueMapSrc: string;
    whatsappHref: string;
    phoneHref: string;
    emailHelloHref: string;
    announcementText: string | null;
};

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

export function buildBureauBubbles(site: SiteShared): BureauBubble[] {
    return [
        {
            kind: 'link',
            icon: MessageCircle,
            name: 'WhatsApp ons',
            sub: 'Snelste antwoord',
            href: site.whatsappHref,
        },
        {
            kind: 'link',
            icon: Phone,
            name: 'Bel ons',
            sub: 'Ma–Vr · 10–18u',
            href: site.phoneHref,
        },
        {
            kind: 'link',
            icon: Mail,
            name: 'E-mail ons',
            sub: site.emailHello,
            href: site.emailHelloHref,
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
    ];
}

export function buildContactDockItems(site: SiteShared): Array<
    | { icon: LucideIcon; label: string; href: string }
    | { icon: LucideIcon; label: string; panel: BureauPanelId }
> {
    return [
        { icon: MessageCircle, label: 'WhatsApp ons', href: site.whatsappHref },
        { icon: Phone, label: 'Bel ons', href: site.phoneHref },
        { icon: Mail, label: 'E-mail ons', href: site.emailHelloHref },
        { icon: Package, label: 'Bestelstatus', panel: 'bestel' },
        { icon: Heart, label: 'Maison Care', panel: 'care' },
        { icon: CalendarDays, label: 'Boek een afspraak', panel: 'afspraak' },
        { icon: Video, label: 'Privé consult', panel: 'concierge' },
        { icon: MapPin, label: 'Vind uw Boutique', panel: 'boutique' },
        { icon: HelpCircle, label: 'Veelgestelde vragen', panel: 'faq' },
        { icon: MessageSquare, label: 'Uw mening', panel: 'feedback' },
    ];
}

export const BUREAU_PANEL_IDS: readonly BureauPanelId[] = [
    'bestel',
    'care',
    'afspraak',
    'concierge',
    'boutique',
    'faq',
    'feedback',
] as const;
