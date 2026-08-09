import {
    CalendarDays,
    Heart,
    HelpCircle,
    Mail,
    MapPin,
    MessageCircle,
    Package,
    Phone,
    Video,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';

/** The panel the eight items live in. `panel` targets a bureau on /contact. */
const ITEMS = [
    {
        icon: MessageCircle,
        label: 'WhatsApp ons',
        href: 'https://wa.me/32400000000',
    },
    { icon: Phone, label: 'Bel ons', href: 'tel:+32400000000' },
    { icon: Mail, label: 'E-mail ons', href: 'mailto:hello@maisonanversa.com' },
    { icon: Package, label: 'Bestelstatus', panel: 'bestel' },
    { icon: Heart, label: 'Maison Care', panel: 'care' },
    { icon: CalendarDays, label: 'Boek een afspraak', panel: 'afspraak' },
    { icon: Video, label: 'Privé consult', panel: 'concierge' },
    { icon: MapPin, label: 'Vind uw Boutique', panel: 'boutique' },
    { icon: HelpCircle, label: 'Veelgestelde vragen', panel: 'faq' },
] as const;

/**
 * The floating help dock, bottom right on every page.
 *
 * Items that open a bureau on the contact page navigate to it with the panel in
 * the URL fragment. The prototype navigated and then guessed at a 380ms timeout
 * before prising the panel open, which meant the deep link was unshareable and
 * the timing was a race.
 */
export function ContactDock() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const panel = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        const onPointerDown = (event: PointerEvent) => {
            if (!panel.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('keydown', onKey);
        document.addEventListener('pointerdown', onPointerDown);

        return () => {
            document.removeEventListener('keydown', onKey);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, [open]);

    return (
        <div ref={panel}>
            {open && (
                <div className="fixed right-4 bottom-20 z-91 w-77 max-w-[calc(100vw-32px)] rounded-2xl border border-gold/30 bg-[#241a14] px-4 pt-4.5 pb-3.5 shadow-[0_14px_44px_rgba(0,0,0,0.6)] ma-sm:right-6.5 ma-sm:bottom-25">
                    <div className="mb-3 flex items-center justify-between px-1">
                        <span className="font-serif text-base text-cream">
                            {t('Hoe kunnen wij u helpen?')}
                        </span>

                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            aria-label={t('Sluiten')}
                            className="flex size-11 items-center justify-center text-gold"
                        >
                            <X className="size-4" />
                        </button>
                    </div>

                    <div className="flex flex-col gap-1.75">
                        {ITEMS.map((item) => {
                            const className =
                                'flex w-full items-center gap-3 rounded-[10px] border border-gold/20 px-3.25 py-2.75 text-left font-sans text-[13px] text-cream transition-colors hover:border-gold/40 hover:bg-gold/12';

                            if ('href' in item) {
                                return (
                                    <a
                                        key={item.label}
                                        href={item.href}
                                        target={
                                            item.href.startsWith('http')
                                                ? '_blank'
                                                : undefined
                                        }
                                        rel={
                                            item.href.startsWith('http')
                                                ? 'noopener noreferrer'
                                                : undefined
                                        }
                                        className={className}
                                    >
                                        <item.icon className="size-4.5 shrink-0 text-gold" />
                                        {t(item.label)}
                                    </a>
                                );
                            }

                            return (
                                <MaisonLink
                                    key={item.label}
                                    to="contact"
                                    hash={item.panel}
                                    onClick={() => setOpen(false)}
                                    className={className}
                                >
                                    <item.icon className="size-4.5 shrink-0 text-gold" />
                                    {t(item.label)}
                                </MaisonLink>
                            );
                        })}
                    </div>
                </div>
            )}

            <button
                type="button"
                data-magnetic
                aria-label={t('Maison Anversa — hulp')}
                aria-expanded={open}
                onClick={() => setOpen((wasOpen) => !wasOpen)}
                className="fixed right-4 bottom-4 z-90 flex size-13 items-center justify-center rounded-full border-[1.5px] border-gold bg-choc shadow-[0_8px_30px_rgba(0,0,0,0.55)] transition-transform hover:-translate-y-0.5 hover:scale-104 ma-sm:right-6.5 ma-sm:bottom-6.5 ma-sm:size-15.5"
            >
                <span
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-1.25 rounded-full border border-gold/28"
                />

                <PlaceholderImage
                    asset="maison-facade-house"
                    alt=""
                    captioned={false}
                    ratio={null}
                    className="pointer-events-none size-7 opacity-96 ma-sm:size-9"
                />
            </button>
        </div>
    );
}
