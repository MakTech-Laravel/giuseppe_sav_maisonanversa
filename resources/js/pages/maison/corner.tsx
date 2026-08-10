import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { SuccessPanel } from '@/components/maison/ui/success-panel';

const PACKAGE_ITEMS = [
    {
        num: '01',
        title: 'Heritage Display',
        desc: 'Een elegant display materiaal om Heritage No.001 te presenteren. Ontworpen in de Maison Anversa stijl — crème, chocolade, goud.',
    },
    {
        num: '02',
        title: 'Demo Racket',
        desc: 'Eén Heritage No.001 demo racket voor leden om aan te raken en te voelen. Het verschil zit in de eerste aanraking.',
    },
    {
        num: '03',
        title: 'Heritage Paspoorten',
        desc: 'Een set Heritage Paspoorten voor geïnteresseerde leden — het verhaal van Maison Anversa in boekvorm.',
    },
    {
        num: '04',
        title: 'Branded Materialen',
        desc: 'Kaarten, hangtags en branded elementen die de Club Corner ruimte markeren als Maison Anversa territory.',
    },
    {
        num: '05',
        title: 'Founding Circle Toegang',
        desc: 'Club Corner partners kunnen hun leden toegang geven tot de Heritage Letter en exclusieve Maison Anversa updates.',
    },
    {
        num: '06',
        title: 'Partner Ondersteuning',
        desc: 'Directe lijn met Maison Anversa. Persoonlijke ondersteuning voor vragen, nabestellingen en club events.',
    },
] as const;

const FORMAT_A_INCLUDES = [
    '1 Heritage Display unit',
    '1 Demo racket Heritage No.001',
    '10 Heritage Paspoorten',
    'Branded kaarten en materialen',
    'Toegang tot Heritage Letter voor leden',
    'Kwartaalse update van Maison Anversa',
] as const;

const FORMAT_B_INCLUDES = [
    'Alles van Formaat A',
    'Exclusieve Club Corner vermelding op maisonanversa.com',
    'Maandelijkse update en behind the scenes content',
    'Uitnodiging voor Maison Anversa events',
    'Prioritaire toegang tot toekomstige collecties',
    'Co-branded evenement mogelijkheid',
] as const;

const CRITERIA = [
    'Een premium omgeving die aanvoelt als een privéclub — niet een sporthal',
    'Een gemeenschap van leden die kwaliteit waarderen en investeren in ervaring',
    'Een locatie in een Europese stad met een actieve padel cultuur',
    'Bereidheid om de Maison Anversa identiteit correct en consistent te vertegenwoordigen',
    'Een clubmanagement dat gelooft in langdurige partnerships boven kortetermijnwinst',
    'Minimaal 4 courts en een actieve ledenlijst van 200+ leden',
] as const;

const CLUBS = [
    {
        location: 'België',
        name: 'Antwerpen',
        status: 'In gesprek — 2026',
        pending: true,
    },
    {
        location: 'België',
        name: 'Brussel',
        status: 'Open voor aanvragen',
        pending: false,
    },
    {
        location: 'Nederland',
        name: 'Amsterdam',
        status: 'Open voor aanvragen',
        pending: false,
    },
    {
        location: 'Nederland',
        name: 'Rotterdam',
        status: 'Open voor aanvragen',
        pending: false,
    },
    {
        location: 'Duitsland',
        name: 'Hamburg',
        status: 'Open voor aanvragen',
        pending: false,
    },
    {
        location: 'Uw stad?',
        name: 'Neem contact op',
        status: 'Open voor aanvragen',
        pending: false,
    },
] as const;

const PROCESS_STEPS = [
    {
        num: '01',
        title: 'Aanvraag',
        desc: 'Vul het formulier in met de details van uw club.',
    },
    {
        num: '02',
        title: 'Kennismaking',
        desc: 'Een persoonlijk gesprek met Yusuf Savran over het concept en de mogelijkheden.',
    },
    {
        num: '03',
        title: 'Voorstel',
        desc: 'Een gepersonaliseerd Club Corner voorstel op maat van uw club.',
    },
    {
        num: '04',
        title: 'Lancering',
        desc: 'Uw Club Corner gaat live — met volledige ondersteuning van Maison Anversa.',
    },
] as const;

const COURT_OPTIONS = ['4-6 courts', '7-10 courts', '10+ courts'] as const;

const FORMAT_OPTIONS = [
    'Formaat A — Heritage Corner',
    'Formaat B — Founding Club Corner',
    'Nog niet beslist',
] as const;

const fieldClassName =
    'border border-gold/20 bg-cream2 px-4 py-3 font-serif text-base text-choc outline-none transition-colors placeholder:text-stone focus:border-gold2';

export default function Corner() {
    const { t } = useTranslation();
    const contactRef = useRef<HTMLDivElement>(null);

    function scrollToForm() {
        contactRef.current?.scrollIntoView({ behavior: 'smooth' });
    }

    return (
        <>
            <MaisonSeoHead page="corner" />

            <PageHero
                eyebrow="Maison Anversa"
                title={
                    <>
                        {t('Club')} <em>{t('Corner')}</em>
                    </>
                }
                subtitle={t(
                    'Breng Maison Anversa naar uw padelclub. Een curated ervaring voor clubs die begrijpen dat padel meer is dan een sport.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <div className="grid items-center gap-16 md:grid-cols-2 md:gap-20">
                        <Reveal>
                            <Eyebrow tone="gold2">
                                {t('Wat is Club Corner?')}
                            </Eyebrow>
                            <GoldRule />
                            <h2 className="mb-6 font-serif text-[clamp(28px,3.5vw,48px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                                {t('Een')} <em>{t('thuis')}</em>{' '}
                                {t('voor Maison Anversa in uw club')}
                            </h2>
                            <p className="mb-5 text-base leading-[1.9] text-choc3">
                                {t(
                                    'Club Corner is het fysieke distributieprogramma van Maison Anversa. Geselecteerde padelclubs in Europa kunnen een exclusieve Maison Anversa hoek inrichten — een curated ruimte waar leden Heritage producten kunnen ontdekken, aanraken en bestellen.',
                                )}
                            </p>
                            <p className="mb-5 text-base leading-[1.9] text-choc3">
                                {t(
                                    'Het is geen klassiek verkooppunt. Het is een ervaringsruimte. Uw leden worden deel van het Maison Anversa verhaal.',
                                )}
                            </p>
                            <p className="text-base leading-[1.9] text-choc3">
                                {t(
                                    'Wij selecteren partners zorgvuldig. Niet elke club past bij Maison Anversa — en niet elke club zou dat willen. Club Corner is voor clubs die dezelfde waarden delen: kwaliteit boven kwantiteit, gemeenschap boven commercie.',
                                )}
                            </p>
                        </Reveal>

                        <Reveal className="relative flex aspect-4/5 flex-col items-center justify-center gap-3 overflow-hidden bg-[linear-gradient(140deg,#352722_0%,#291c18_100%)]">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute inset-0 bg-[repeating-linear-gradient(-45deg,transparent,transparent_4px,rgba(141,112,90,0.015)_4px,rgba(141,112,90,0.015)_5px),repeating-linear-gradient(45deg,transparent,transparent_4px,rgba(141,112,90,0.01)_4px,rgba(141,112,90,0.01)_5px)]"
                            />
                            <div className="relative z-1 font-serif text-xl font-medium tracking-[0.2em] text-cream uppercase">
                                {t('Club')} {t('Corner')}
                            </div>
                            <div className="relative z-1 font-sans text-[8px] font-light tracking-[0.3em] text-gold uppercase">
                                {t('Maison Anversa · Partner Programma')}
                            </div>
                            <div className="absolute top-6 right-6 text-right font-serif text-[10px] tracking-[0.2em] text-gold/25 uppercase">
                                {t('European Heritage House')}
                            </div>
                            <div className="absolute bottom-6 left-6 flex size-11 items-center justify-center overflow-hidden border border-gold">
                                <PlaceholderImage
                                    asset="logo-icon"
                                    ratio="1 / 1"
                                    alt="Maison Anversa"
                                    captioned={false}
                                    className="size-full"
                                />
                            </div>
                        </Reveal>
                    </div>
                </Wrap>
            </Section>

            <Section tone="choc2">
                <Wrap>
                    <Eyebrow>{t('Het Club Corner Pakket')}</Eyebrow>
                    <GoldRule />
                    <h2 className="mb-3 font-serif text-[clamp(28px,3.5vw,44px)] leading-[1.1] font-medium [&_em]:text-gold [&_em]:italic">
                        {t('Wat uw club')} <em>{t('ontvangt')}</em>
                    </h2>
                    <p className="mb-14 max-w-140 text-base leading-[1.85] text-sand">
                        {t(
                            'Elke Club Corner partner ontvangt een volledig pakket om de Maison Anversa ervaring correct in te richten. Alles is ontworpen voor consistentie — zodat elk Club Corner in Europa dezelfde premium ervaring biedt.',
                        )}
                    </p>

                    <div className="grid gap-0.5 md:grid-cols-3">
                        {PACKAGE_ITEMS.map((item) => (
                            <Reveal
                                key={item.num}
                                className="border border-gold/10 bg-white/3 px-7 py-9 transition-colors hover:border-gold/25 hover:bg-gold/6"
                            >
                                <div className="mb-4 font-serif text-[48px] leading-none font-light text-gold/18">
                                    {item.num}
                                </div>
                                <h3 className="mb-2 font-serif text-xl font-medium text-cream">
                                    {t(item.title)}
                                </h3>
                                <p className="text-[14px] leading-[1.7] text-sand">
                                    {t(item.desc)}
                                </p>
                            </Reveal>
                        ))}
                    </div>
                </Wrap>
            </Section>

            <Section tone="cream">
                <Wrap>
                    <Reveal className="mx-auto mb-16 max-w-160 text-center">
                        <Eyebrow tone="gold2">
                            {t('Club Corner Formaten')}
                        </Eyebrow>
                        <GoldRule center className="mx-auto" />
                        <h2 className="mb-4 font-serif text-[clamp(28px,3.5vw,44px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                            {t('Kies uw')} <em>{t('formaat')}</em>
                        </h2>
                        <p className="text-base leading-[1.85] text-choc3">
                            {t(
                                'Maison Anversa biedt twee Club Corner formaten — afhankelijk van de grootte van uw club en uw ambitie als partner.',
                            )}
                        </p>
                    </Reveal>

                    <div className="mb-16 grid gap-0.5 bg-sand md:grid-cols-2">
                        <Reveal className="bg-cream px-10 py-12">
                            <span className="mb-4 block font-sans text-[9px] font-medium tracking-[0.3em] text-gold2 uppercase">
                                {t('Formaat A')}
                            </span>
                            <h3 className="mb-4 font-serif text-[28px] leading-[1.2] font-medium text-choc">
                                {t('Heritage Corner')}
                            </h3>
                            <p className="mb-6 text-[15px] leading-[1.85] text-choc3">
                                {t(
                                    'Het instapformaat voor clubs die kennis willen maken met Maison Anversa. Compact, elegant, impactvol.',
                                )}
                            </p>
                            <ul className="flex flex-col gap-2.5">
                                {FORMAT_A_INCLUDES.map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-start gap-2.5 text-[14px] leading-normal text-choc3 before:mt-0.5 before:shrink-0 before:font-sans before:text-xs before:text-gold2 before:content-['→']"
                                    >
                                        {t(item)}
                                    </li>
                                ))}
                            </ul>
                        </Reveal>

                        <Reveal className="bg-cream px-10 py-12">
                            <span className="mb-4 block font-sans text-[9px] font-medium tracking-[0.3em] text-gold2 uppercase">
                                {t('Formaat B')}
                            </span>
                            <h3 className="mb-4 font-serif text-[28px] leading-[1.2] font-medium text-choc">
                                {t('Founding Club Corner')}
                            </h3>
                            <p className="mb-6 text-[15px] leading-[1.85] text-choc3">
                                {t(
                                    'Het premium partnerschap voor clubs die een centrale rol willen spelen in het Maison Anversa netwerk.',
                                )}
                            </p>
                            <ul className="flex flex-col gap-2.5">
                                {FORMAT_B_INCLUDES.map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-start gap-2.5 text-[14px] leading-normal text-choc3 before:mt-0.5 before:shrink-0 before:font-sans before:text-xs before:text-gold2 before:content-['→']"
                                    >
                                        {t(item)}
                                    </li>
                                ))}
                            </ul>
                        </Reveal>
                    </div>

                    <div className="text-center">
                        <MaisonButton variant="choc" onClick={scrollToForm}>
                            {t('Vraag een Partnership Gesprek Aan →')}
                        </MaisonButton>
                    </div>
                </Wrap>
            </Section>

            <Section tone="cream2">
                <Wrap>
                    <div className="grid gap-16 md:grid-cols-2 md:gap-20">
                        <Reveal>
                            <Eyebrow tone="gold2">
                                {t('Wie komen in aanmerking?')}
                            </Eyebrow>
                            <GoldRule />
                            <h2 className="mb-6 font-serif text-[clamp(26px,3vw,40px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                                {t('Niet elke club.')}
                                <br />
                                {t('De')} <em>{t('juiste')}</em> {t('club.')}
                            </h2>
                            <p className="mb-5 text-base leading-[1.85] text-choc3">
                                {t(
                                    'Maison Anversa selecteert Club Corner partners op basis van gedeelde waarden — niet op basis van grootte of omzet. Wij zoeken clubs die begrijpen dat padel een cultuur is, geen sport.',
                                )}
                            </p>
                            <p className="text-base leading-[1.85] text-choc3">
                                {t(
                                    'Als u deze criteria herkent in uw club, nodigen wij u uit om contact op te nemen.',
                                )}
                            </p>
                        </Reveal>

                        <Reveal as="ul" className="flex flex-col gap-4">
                            {CRITERIA.map((item) => (
                                <li
                                    key={item}
                                    className="flex items-start gap-4 border-b border-gold/15 pb-4 text-[15px] leading-normal text-choc3 before:mt-0.5 before:shrink-0 before:font-sans before:text-[13px] before:text-gold2 before:content-['✓']"
                                >
                                    {t(item)}
                                </li>
                            ))}
                        </Reveal>
                    </div>
                </Wrap>
            </Section>

            <Section tone="dark" padded className="py-20 text-center">
                <Wrap>
                    <blockquote className="mx-auto mb-5 max-w-180 font-serif text-[clamp(20px,2.5vw,32px)] leading-normal text-cream italic">
                        {t(
                            '"Club Corner is geen verkoopkanaal. Het is een uitnodiging om deel te worden van iets dat nog maar net begonnen is."',
                        )}
                    </blockquote>
                    <cite className="font-sans text-[10px] tracking-[0.25em] text-gold uppercase not-italic">
                        {t('— Yusuf Savran, Oprichter Maison Anversa')}
                    </cite>
                </Wrap>
            </Section>

            <Section tone="dark">
                <Wrap>
                    <div className="mb-12 grid items-end gap-10 md:grid-cols-[1fr_2fr] md:gap-15">
                        <Reveal>
                            <Eyebrow>{t('Het Netwerk')}</Eyebrow>
                            <h2 className="font-serif text-[clamp(26px,3vw,40px)] leading-[1.1] font-medium text-cream [&_em]:text-gold [&_em]:italic">
                                {t('Onze Club')} <em>{t('Corners')}</em>
                            </h2>
                        </Reveal>
                        <Reveal>
                            <p className="text-base leading-[1.85] text-sand">
                                {t(
                                    'Maison Anversa Club Corners komen eraan in geselecteerde steden doorheen Europa. Ben jij de eerste in jouw stad?',
                                )}
                            </p>
                        </Reveal>
                    </div>

                    <div className="grid gap-0.5 sm:grid-cols-2 lg:grid-cols-3">
                        {CLUBS.map((club) => (
                            <Reveal
                                key={`${club.location}-${club.name}`}
                                className="border border-gold/10 bg-white/3 px-7 py-8"
                            >
                                <span className="mb-2 block font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                                    {t(club.location)}
                                </span>
                                <div className="mb-2 font-serif text-2xl font-medium text-cream">
                                    {t(club.name)}
                                </div>
                                <div
                                    className={
                                        club.pending
                                            ? 'font-sans text-[10px] tracking-[0.15em] text-stone uppercase'
                                            : 'font-sans text-[10px] tracking-[0.15em] text-gold uppercase'
                                    }
                                >
                                    {t(club.status)}
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </Wrap>
            </Section>

            <Section tone="cream" id="cc-contact-form">
                <div ref={contactRef}>
                    <Wrap>
                        <div className="grid gap-16 md:grid-cols-2 md:gap-20">
                            <div>
                                <Eyebrow tone="gold2">
                                    {t('Partnership Aanvraag')}
                                </Eyebrow>
                                <GoldRule />
                                <h2 className="mb-5 font-serif text-4xl font-medium [&_em]:text-gold2 [&_em]:italic">
                                    {t('Laten we')} <em>{t('praten')}</em>
                                </h2>
                                <p className="mb-8 text-base leading-[1.85] text-choc3">
                                    {t(
                                        'Stuur ons een bericht en wij nemen binnen 48 uur persoonlijk contact op. Geen automatische responses — een echt gesprek over wat een partnership voor uw club kan betekenen.',
                                    )}
                                </p>

                                <div className="flex flex-col">
                                    {PROCESS_STEPS.map((step) => (
                                        <div
                                            key={step.num}
                                            className="flex items-start gap-5 border-b border-gold/15 py-5"
                                        >
                                            <div className="w-8 shrink-0 font-serif text-[28px] leading-none font-light text-gold/40">
                                                {step.num}
                                            </div>
                                            <div>
                                                <h4 className="mb-1 font-serif text-lg font-medium text-choc">
                                                    {t(step.title)}
                                                </h4>
                                                <p className="text-[14px] leading-[1.7] text-choc3">
                                                    {t(step.desc)}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <CornerPartnershipForm />
                        </div>
                    </Wrap>
                </div>
            </Section>
        </>
    );
}

function CornerPartnershipForm() {
    const { t } = useTranslation();
    const [sent, setSent] = useState(false);

    function onSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!event.currentTarget.reportValidity()) {
            return;
        }

        setSent(true);
    }

    if (sent) {
        return (
            <SuccessPanel
                title={t('Aanvraag ontvangen.')}
                icon="✓"
                className="rounded border border-gold/15 bg-cream2 px-8 py-10 text-left"
            >
                <p>{t('Wij nemen binnen 48 uur persoonlijk contact op.')}</p>
            </SuccessPanel>
        );
    }

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-4">
            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-club-name"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Naam club')}
                </label>
                <input
                    id="corner-club-name"
                    name="clubName"
                    type="text"
                    required
                    placeholder={t('Naam van uw padelclub')}
                    className={fieldClassName}
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-contact"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Contactpersoon')}
                </label>
                <input
                    id="corner-contact"
                    name="contactPerson"
                    type="text"
                    required
                    placeholder={t('Uw naam en functie')}
                    className={fieldClassName}
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-email"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('E-mailadres')}
                </label>
                <input
                    id="corner-email"
                    name="email"
                    type="email"
                    required
                    placeholder={t('uw@emailadres.be')}
                    className={fieldClassName}
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-location"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Stad & Land')}
                </label>
                <input
                    id="corner-location"
                    name="location"
                    type="text"
                    required
                    placeholder={t('Antwerpen, België')}
                    className={fieldClassName}
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-courts"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Aantal courts')}
                </label>
                <select
                    id="corner-courts"
                    name="courts"
                    required
                    defaultValue={COURT_OPTIONS[0]}
                    className={fieldClassName}
                >
                    {COURT_OPTIONS.map((option) => (
                        <option key={option} value={option}>
                            {t(option)}
                        </option>
                    ))}
                </select>
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-format"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Gewenst formaat')}
                </label>
                <select
                    id="corner-format"
                    name="format"
                    required
                    defaultValue={FORMAT_OPTIONS[0]}
                    className={fieldClassName}
                >
                    {FORMAT_OPTIONS.map((option) => (
                        <option key={option} value={option}>
                            {t(option)}
                        </option>
                    ))}
                </select>
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-about"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Vertel ons over uw club')}
                </label>
                <textarea
                    id="corner-about"
                    name="about"
                    required
                    placeholder={t(
                        'Beschrijf kort de sfeer en identiteit van uw club...',
                    )}
                    className={`${fieldClassName} min-h-25 resize-y`}
                />
            </div>

            <MaisonButton type="submit" variant="gold" block>
                {t('Verstuur Partnership Aanvraag')}
            </MaisonButton>
        </form>
    );
}
