import { usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    BureauFieldRow,
    BureauForm,
    BureauInput,
    BureauSelect,
    BureauTextarea,
    useBureauVisitor,
} from '@/components/maison/contact/bureau-form';
import {
    BUREAU_PANEL_IDS,
    buildBureauBubbles,
} from '@/components/maison/contact/contact-data';
import type {
    BureauPanelId,
    SiteShared,
} from '@/components/maison/contact/contact-data';
import { ContactFaq } from '@/components/maison/contact/contact-faq';
import { Section, Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';

const PANEL_TITLES: Record<BureauPanelId, string> = {
    bestel: 'Track your order',
    care: 'Maison Care',
    afspraak: 'Book an appointment',
    concierge: 'Privé consult',
    atelier: 'Find our atelier',
    faq: 'Veelgestelde vragen',
    feedback: 'Jouw mening',
};

function BureauBubbleButton({
    icon: Icon,
    name,
    sub,
    active,
    onClick,
}: {
    icon: LucideIcon;
    name: string;
    sub: string;
    active: boolean;
    onClick: () => void;
}) {
    const { t } = useTranslation();

    return (
        <button
            type="button"
            aria-expanded={active}
            onClick={onClick}
            className={cn(
                'flex w-fit max-w-full cursor-pointer items-center gap-3.5 rounded-[22px_22px_6px_22px] border bg-gold/5 px-5.5 py-3.75 text-left font-sans text-cream transition-all hover:-translate-x-0.5 hover:border-gold hover:bg-gold/14',
                active ? 'border-gold bg-gold/14' : 'border-gold/35',
            )}
        >
            <Icon className="size-5.5 shrink-0 text-gold" />
            <span className="flex flex-col gap-0.5">
                <span className="text-sm tracking-[0.02em]">{t(name)}</span>
                <span className="text-[9px] tracking-[0.18em] text-sand uppercase">
                    {t(sub)}
                </span>
            </span>
        </button>
    );
}

function BureauBubbleLink({
    icon: Icon,
    name,
    sub,
    href,
}: {
    icon: LucideIcon;
    name: string;
    sub: string;
    href: string;
}) {
    const { t } = useTranslation();
    const external = href.startsWith('http');

    return (
        <a
            href={href}
            target={external ? '_blank' : undefined}
            rel={external ? 'noopener noreferrer' : undefined}
            className="flex w-fit max-w-full items-center gap-3.5 rounded-[22px_22px_6px_22px] border border-gold/35 bg-gold/5 px-5.5 py-3.75 text-left font-sans text-cream no-underline transition-all hover:-translate-x-0.5 hover:border-gold hover:bg-gold/14"
        >
            <Icon className="size-5.5 shrink-0 text-gold" />
            <span className="flex flex-col gap-0.5">
                <span className="text-sm tracking-[0.02em]">{t(name)}</span>
                <span className="text-[9px] tracking-[0.18em] text-sand uppercase">
                    {sub.includes('@') ? sub : t(sub)}
                </span>
            </span>
        </a>
    );
}

function BureauPanel({
    id,
    open,
    title,
    children,
}: {
    id: BureauPanelId;
    open: boolean;
    title: string;
    children: ReactNode;
}) {
    const { t } = useTranslation();

    if (!open) {
        return null;
    }

    return (
        <div
            id={`bp-${id}`}
            className="w-full animate-in rounded-lg border border-gold/20 bg-black/18 px-7 py-7.5 duration-300 fade-in slide-in-from-bottom-2"
        >
            <h2 className="mb-2 font-serif text-[22px] font-normal text-cream">
                {t(title)}
            </h2>
            {children}
        </div>
    );
}

function BureauNote({ children }: { children: string }) {
    const { t } = useTranslation();

    return (
        <p className="mt-3.5 font-sans text-[10px] tracking-[0.18em] text-gold uppercase opacity-80">
            {t(children)}
        </p>
    );
}

function BureauPanelBody({
    id,
    mapSrc,
    faqs,
}: {
    id: BureauPanelId;
    mapSrc: string;
    faqs: Array<{ question: string; answer: string }>;
}) {
    const { t } = useTranslation();
    const visitor = useBureauVisitor();

    switch (id) {
        case 'bestel':
            return (
                <>
                    <p className="text-sm leading-[1.8] text-sand">
                        {t(
                            'Once Heritage Nº 001 delivers, you can track the status of your edition number here. Until then, we will look up your reservation manually — send us your name or reservation number via WhatsApp or email.',
                        )}
                    </p>
                    <BureauNote>
                        Coming soon — final at launch
                    </BureauNote>
                </>
            );
        case 'care':
            return (
                <>
                    <p className="text-sm leading-[1.8] text-sand">
                        {t(
                            'Each Heritage racket is hand-finished and deserves ongoing care. We advise on leather maintenance, repairs and warranty. When in doubt, send the racket back to our atelier in Antwerp for inspection.',
                        )}
                    </p>
                    <BureauNote>
                        Voorgenomen servicebeleid — definitief bij lancering
                    </BureauNote>
                </>
            );
        case 'afspraak':
            return (
                <>
                    <p className="text-sm leading-[1.8] text-sand">
                        {t(
                            'Visit our atelier in Antwerp for a private experience: hold the racket, feel the leather, and discover your number. Request a moment — we will confirm personally.',
                        )}
                    </p>
                    <BureauForm
                        kind="appointment"
                        submitLabel="Send request"
                    >
                        <BureauFieldRow>
                            <BureauInput
                                name="name"
                                placeholder="Naam"
                                required
                                defaultValue={visitor.name}
                            />
                            <BureauInput
                                name="email"
                                type="email"
                                placeholder="E-mailadres"
                                required
                                defaultValue={visitor.email}
                            />
                        </BureauFieldRow>
                        <BureauFieldRow>
                            <BureauInput
                                name="datum"
                                type="date"
                                placeholder="Datum"
                            />
                            <BureauSelect
                                name="moment"
                                options={[
                                    'Preferred time',
                                    'Ochtend',
                                    'Middag',
                                    'Einde dag',
                                ]}
                            />
                        </BureauFieldRow>
                        <BureauTextarea
                            name="message"
                            placeholder="Brief note (optional)"
                        />
                    </BureauForm>
                    <BureauNote>Request will be confirmed via email</BureauNote>
                </>
            );
        case 'concierge':
            return (
                <>
                    <p className="text-sm leading-[1.8] text-sand">
                        {t(
                            'Speaking with a Maison specialist: a personal video call about your number, the finish, the leather and the monogram. No pressure, no rush — just tailored advice.',
                        )}
                    </p>
                    <BureauForm kind="consult" submitLabel="Request consultation">
                        <BureauFieldRow>
                            <BureauInput
                                name="name"
                                placeholder="Naam"
                                required
                                defaultValue={visitor.name}
                            />
                            <BureauInput
                                name="email"
                                type="email"
                                placeholder="E-mailadres"
                                required
                                defaultValue={visitor.email}
                            />
                        </BureauFieldRow>
                        <BureauFieldRow>
                            <BureauSelect
                                name="soort"
                                options={[
                                    'Video call',
                                    'Phone call',
                                    'Atelier visit Antwerp',
                                ]}
                            />
                            <BureauSelect
                                name="moment"
                                options={[
                                    'Preferred time',
                                    'Ochtend',
                                    'Middag',
                                    'Einde dag',
                                ]}
                            />
                        </BureauFieldRow>
                        <BureauTextarea
                            name="message"
                            placeholder="What would you like to discuss? (optional)"
                        />
                    </BureauForm>
                    <BureauNote>
                        Wij bevestigen persoonlijk binnen 24 uur · voorgenomen
                        service
                    </BureauNote>
                </>
            );
        case 'atelier':
            return (
                <>
                    <p className="text-sm leading-[1.8] text-sand">
                        {t(
                            'Maison Anversa is located in the heart of Antwerp. Visit by appointment — we welcome you personally.',
                        )}
                    </p>
                    <p className="mt-1.5 font-serif text-lg text-gold">
                        {t('Central Antwerp · Belgium')}
                    </p>
                    <iframe
                        title={t('Maison Anversa — Antwerp')}
                        loading="lazy"
                        src={mapSrc}
                        className="mt-4 h-60 w-full rounded-md border border-gold/20 brightness-[0.85] grayscale-[0.3]"
                    />
                </>
            );
        case 'faq':
            return <ContactFaq faqs={faqs} />;
        case 'feedback':
            return (
                <>
                    <p className="text-sm leading-[1.8] text-sand">
                        {t(
                            'Your feedback shapes the house. Tell us what you thought — from the site to the racket.',
                        )}
                    </p>
                    <BureauForm kind="feedback" submitLabel="Send feedback">
                        <BureauFieldRow>
                            <BureauInput
                                name="name"
                                placeholder="Naam"
                                required
                                defaultValue={visitor.name}
                            />
                            <BureauInput
                                name="email"
                                type="email"
                                placeholder="E-mailadres"
                                required
                                defaultValue={visitor.email}
                            />
                        </BureauFieldRow>
                        <BureauSelect
                            name="ervaring"
                            options={[
                                'Your experience',
                                'Uitstekend',
                                'Goed',
                                'Neutraal',
                                'Kan beter',
                            ]}
                            className="col-span-full"
                        />
                        <BureauTextarea
                            name="message"
                            rows={4}
                            placeholder="Your feedback..."
                        />
                    </BureauForm>
                </>
            );
        default:
            return null;
    }
}

function panelIdFromHash(hash: string): BureauPanelId | null {
    const raw = hash.replace(/^#/, '');
    const id = (raw === 'boutique' ? 'atelier' : raw) as BureauPanelId;

    return BUREAU_PANEL_IDS.includes(id) ? id : null;
}

function writePanelHash(id: BureauPanelId | null): void {
    const next = id
        ? `#${id}`
        : `${window.location.pathname}${window.location.search}`;
    const current = `${window.location.pathname}${window.location.search}${window.location.hash}`;

    if (current === next || window.location.hash === next) {
        return;
    }

    history.replaceState(null, '', next);
}

function scrollToPanel(id: BureauPanelId): void {
    requestAnimationFrame(() => {
        document.getElementById(`bp-${id}`)?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    });
}

/**
 * The contact bureau: chat bubbles that reveal one collapsible panel at a time,
 * directly under the chosen option.
 *
 * Deep links from `ContactDock` arrive with a URL fragment (`#faq`, `#care`, …).
 * React state owns which panel is open so we never rely on prototype onclick
 * strings or a post-navigation timeout.
 */
export function ContactBureau() {
    const { site, faqs } = usePage<{
        site: SiteShared;
        faqs: Array<{ question: string; answer: string }>;
    }>().props;
    const bureauBubbles = buildBureauBubbles(site);
    const [openPanel, setOpenPanel] = useState<BureauPanelId | null>(() =>
        typeof window !== 'undefined'
            ? panelIdFromHash(window.location.hash)
            : null,
    );

    function revealPanel(id: BureauPanelId | null) {
        setOpenPanel(id);
        writePanelHash(id);

        if (!id) {
            return;
        }

        scrollToPanel(id);
    }

    function togglePanel(id: BureauPanelId) {
        revealPanel(openPanel === id ? null : id);
    }

    useEffect(() => {
        const initialId = panelIdFromHash(window.location.hash);

        if (initialId) {
            scrollToPanel(initialId);
        }

        function onHashChange() {
            const id = panelIdFromHash(window.location.hash);
            setOpenPanel(id);

            if (id) {
                scrollToPanel(id);
            }
        }

        window.addEventListener('hashchange', onHashChange);

        return () => window.removeEventListener('hashchange', onHashChange);
    }, []);

    return (
        <Section tone="dark">
            <Wrap>
                <div className="mx-auto flex max-w-190 flex-col gap-3">
                    {bureauBubbles.map((bubble) => (
                        <div
                            key={bubble.name}
                            className="flex flex-col items-end gap-3"
                        >
                            <div className="flex w-full max-w-140 justify-end">
                                {bubble.kind === 'link' ? (
                                    <BureauBubbleLink
                                        icon={bubble.icon}
                                        name={bubble.name}
                                        sub={bubble.sub}
                                        href={bubble.href}
                                    />
                                ) : (
                                    <BureauBubbleButton
                                        icon={bubble.icon}
                                        name={bubble.name}
                                        sub={bubble.sub}
                                        active={openPanel === bubble.panel}
                                        onClick={() =>
                                            togglePanel(bubble.panel)
                                        }
                                    />
                                )}
                            </div>

                            {bubble.kind === 'panel' ? (
                                <BureauPanel
                                    id={bubble.panel}
                                    open={openPanel === bubble.panel}
                                    title={PANEL_TITLES[bubble.panel]}
                                >
                                    <BureauPanelBody
                                        id={bubble.panel}
                                        mapSrc={site.boutiqueMapSrc}
                                        faqs={faqs}
                                    />
                                </BureauPanel>
                            ) : null}
                        </div>
                    ))}
                </div>
            </Wrap>
        </Section>
    );
}
