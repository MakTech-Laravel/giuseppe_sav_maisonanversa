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
} from '@/components/maison/contact/bureau-form';
import {
    BOUTIQUE_MAP_SRC,
    BUREAU_BUBBLES,
    BUREAU_PANEL_IDS,
    type BureauPanelId,
} from '@/components/maison/contact/contact-data';
import { ContactFaq } from '@/components/maison/contact/contact-faq';
import { Section, Wrap } from '@/components/maison/ui/section';

function BureauBubbleButton({
    icon: Icon,
    name,
    sub,
    onClick,
}: {
    icon: LucideIcon;
    name: string;
    sub: string;
    onClick: () => void;
}) {
    const { t } = useTranslation();

    return (
        <button
            type="button"
            onClick={onClick}
            className="flex w-fit max-w-full cursor-pointer items-center gap-3.5 rounded-[22px_22px_6px_22px] border border-gold/35 bg-gold/5 px-5.5 py-3.75 text-left font-sans text-cream transition-all hover:-translate-x-0.5 hover:border-gold hover:bg-gold/14"
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
            className="animate-in fade-in slide-in-from-bottom-2 rounded-lg border border-gold/20 bg-black/18 px-7 py-7.5 duration-300"
        >
            <h3 className="mb-2 font-serif text-[22px] font-normal text-cream">
                {t(title)}
            </h3>
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

/**
 * The contact bureau: chat bubbles that reveal seven collapsible panels.
 *
 * Deep links from `ContactDock` arrive with a URL fragment (`#faq`, `#care`, …).
 * React state owns which panels are open so we never rely on prototype onclick
 * strings or a post-navigation timeout.
 */
export function ContactBureau() {
    const { t } = useTranslation();
    const [openPanels, setOpenPanels] = useState<ReadonlySet<BureauPanelId>>(
        () => new Set(),
    );

    function togglePanel(id: BureauPanelId) {
        setOpenPanels((current) => {
            const next = new Set(current);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    }

    function openPanelFromHash(hash: string) {
        const id = hash.replace(/^#/, '') as BureauPanelId;

        if (!BUREAU_PANEL_IDS.includes(id)) {
            return;
        }

        setOpenPanels(new Set([id]));

        requestAnimationFrame(() => {
            document.getElementById(`bp-${id}`)?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        });
    }

    useEffect(() => {
        openPanelFromHash(window.location.hash);

        function onHashChange() {
            openPanelFromHash(window.location.hash);
        }

        window.addEventListener('hashchange', onHashChange);

        return () => window.removeEventListener('hashchange', onHashChange);
    }, []);

    return (
        <Section tone="dark">
            <Wrap>
                <div className="mx-auto flex max-w-140 flex-col items-end gap-3">
                    {BUREAU_BUBBLES.map((bubble) =>
                        bubble.kind === 'link' ? (
                            <BureauBubbleLink
                                key={bubble.name}
                                icon={bubble.icon}
                                name={bubble.name}
                                sub={bubble.sub}
                                href={bubble.href}
                            />
                        ) : (
                            <BureauBubbleButton
                                key={bubble.name}
                                icon={bubble.icon}
                                name={bubble.name}
                                sub={bubble.sub}
                                onClick={() => togglePanel(bubble.panel)}
                            />
                        ),
                    )}
                </div>

                <div className="mx-auto mt-10 flex max-w-190 flex-col gap-4.5">
                    <BureauPanel
                        id="bestel"
                        open={openPanels.has('bestel')}
                        title="Uw bestelling volgen"
                    >
                        <p className="text-sm leading-[1.8] text-sand">
                            {t(
                                'Eens Heritage No.001 levert, volgt u hier de status van uw editienummer. Tot die tijd zoeken wij uw reservering handmatig op — stuur ons uw naam of reserveringsnummer via WhatsApp of e-mail.',
                            )}
                        </p>
                        <BureauNote>
                            Binnenkort beschikbaar — definitief bij lancering
                        </BureauNote>
                    </BureauPanel>

                    <BureauPanel
                        id="care"
                        open={openPanels.has('care')}
                        title="Maison Care"
                    >
                        <p className="text-sm leading-[1.8] text-sand">
                            {t(
                                'Elke Heritage-racket wordt met de hand afgewerkt en verdient blijvende zorg. Wij adviseren over lederonderhoud, herstellingen en garantie. Bij twijfel stuurt u de racket terug naar ons atelier in Antwerpen voor inspectie.',
                            )}
                        </p>
                        <BureauNote>
                            Voorgenomen servicebeleid — definitief bij lancering
                        </BureauNote>
                    </BureauPanel>

                    <BureauPanel
                        id="afspraak"
                        open={openPanels.has('afspraak')}
                        title="Boek een afspraak"
                    >
                        <p className="text-sm leading-[1.8] text-sand">
                            {t(
                                'Bezoek ons atelier in Antwerpen voor een privé-beleving: houd de racket vast, voel het leder, en ontdek uw nummer. Vraag een moment aan — wij bevestigen persoonlijk.',
                            )}
                        </p>

                        <BureauForm
                            subject="Afspraak aanvraag"
                            submitLabel="Aanvraag versturen"
                        >
                            <BureauFieldRow>
                                <BureauInput
                                    name="naam"
                                    placeholder="Naam"
                                    required
                                />
                                <BureauInput
                                    name="email"
                                    type="email"
                                    placeholder="E-mailadres"
                                    required
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
                                        'Voorkeur moment',
                                        'Ochtend',
                                        'Middag',
                                        'Einde dag',
                                    ]}
                                />
                            </BureauFieldRow>

                            <BureauTextarea
                                name="bericht"
                                placeholder="Korte toelichting (optioneel)"
                            />
                        </BureauForm>

                        <BureauNote>
                            Aanvraag wordt bevestigd via e-mail
                        </BureauNote>
                    </BureauPanel>

                    <BureauPanel
                        id="concierge"
                        open={openPanels.has('concierge')}
                        title="Privé consult"
                    >
                        <p className="text-sm leading-[1.8] text-sand">
                            {t(
                                'Sprekend met een Maison-specialist: een persoonlijk videogesprek over uw nummer, de afwerking, het leder en het monogram. Geen druk, geen haast — alleen advies op maat.',
                            )}
                        </p>

                        <BureauForm
                            subject="Privé consult aanvraag"
                            submitLabel="Consult aanvragen"
                        >
                            <BureauFieldRow>
                                <BureauInput
                                    name="naam"
                                    placeholder="Naam"
                                    required
                                />
                                <BureauInput
                                    name="email"
                                    type="email"
                                    placeholder="E-mailadres"
                                    required
                                />
                            </BureauFieldRow>

                            <BureauFieldRow>
                                <BureauSelect
                                    name="soort"
                                    options={[
                                        'Videogesprek',
                                        'Telefoongesprek',
                                        'Atelierbezoek Antwerpen',
                                    ]}
                                />
                                <BureauSelect
                                    name="moment"
                                    options={[
                                        'Voorkeur moment',
                                        'Ochtend',
                                        'Middag',
                                        'Einde dag',
                                    ]}
                                />
                            </BureauFieldRow>

                            <BureauTextarea
                                name="bericht"
                                placeholder="Waarover wilt u spreken? (optioneel)"
                            />
                        </BureauForm>

                        <BureauNote>
                            Wij bevestigen persoonlijk binnen 24 uur · voorgenomen service
                        </BureauNote>
                    </BureauPanel>

                    <BureauPanel
                        id="boutique"
                        open={openPanels.has('boutique')}
                        title="Vind uw Boutique"
                    >
                        <p className="text-sm leading-[1.8] text-sand">
                            {t(
                                'Maison Anversa is gevestigd in het hart van Antwerpen. Bezoek op afspraak — wij verwelkomen u graag persoonlijk.',
                            )}
                        </p>
                        <p className="mt-1.5 font-serif text-lg text-gold">
                            {t('Centrum Antwerpen · België')}
                        </p>
                        <iframe
                            title={t('Maison Anversa — Antwerpen')}
                            loading="lazy"
                            src={BOUTIQUE_MAP_SRC}
                            className="mt-4 h-60 w-full rounded-md border border-gold/20 brightness-[0.85] grayscale-[0.3]"
                        />
                    </BureauPanel>

                    <BureauPanel
                        id="faq"
                        open={openPanels.has('faq')}
                        title="Veelgestelde vragen"
                    >
                        <ContactFaq />
                    </BureauPanel>

                    <BureauPanel
                        id="feedback"
                        open={openPanels.has('feedback')}
                        title="Uw mening"
                    >
                        <p className="text-sm leading-[1.8] text-sand">
                            {t(
                                'Uw feedback vormt het huis mee. Vertel ons wat u vond — van de site tot de racket.',
                            )}
                        </p>

                        <BureauForm
                            subject="Feedback"
                            submitLabel="Verstuur feedback"
                        >
                            <BureauFieldRow>
                                <BureauInput name="naam" placeholder="Naam" />
                                <BureauInput
                                    name="email"
                                    type="email"
                                    placeholder="E-mailadres"
                                />
                            </BureauFieldRow>

                            <BureauSelect
                                name="ervaring"
                                options={[
                                    'Uw ervaring',
                                    'Uitstekend',
                                    'Goed',
                                    'Neutraal',
                                    'Kan beter',
                                ]}
                                className="col-span-full"
                            />

                            <BureauTextarea
                                name="bericht"
                                rows={4}
                                placeholder="Uw feedback..."
                            />
                        </BureauForm>
                    </BureauPanel>
                </div>
            </Wrap>
        </Section>
    );
}
