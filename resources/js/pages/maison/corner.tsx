import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { SuccessPanel } from '@/components/maison/ui/success-panel';
import { useLocale } from '@/hooks/use-locale';
import { inquire as cornerInquire } from '@/routes/maison/corner';

const PACKAGE_ITEMS = [
    {
        num: '01',
        title: 'Heritage Display',
        desc: 'A display cabinet in the Maison Anversa style (cream, chocolate and gold) to present Heritage No.001.',
    },
    {
        num: '02',
        title: 'Demo racket',
        desc: 'A demo model of Heritage No.001 for members to hold and try. Club Corners present and sell the permanent model. The numbered Founding Edition (Nº 001–100) is available online only and will never be made again.',
    },
    {
        num: '03',
        title: 'Heritage brochures',
        desc: 'Brochures telling the story of Maison Anversa and Heritage No.001, for interested members.',
    },
    {
        num: '04',
        title: 'Brand materials',
        desc: 'Cards, price tags and brand elements that make your Club Corner recognisable as a Maison Anversa space.',
    },
    {
        num: '05',
        title: 'Sales & partner fee',
        desc: 'Your club sells alongside us and receives a partner fee for every racket sold. We take care of delivery and service.',
    },
    {
        num: '06',
        title: 'Personal support',
        desc: 'A direct line to Maison Anversa for questions, reorders and club events. Your members also receive access to the Heritage Letter.',
    },
] as const;

const FORMAT_A_INCLUDES = [
    '1 display cabinet',
    '1 demo racket',
    'Heritage brochures',
    'Brand cards and materials',
    'Sales with partner fee',
    'Heritage Letter for your members',
    'Quarterly update from Maison Anversa',
] as const;

const FORMAT_B_INCLUDES = [
    'Everything in Format A',
    'Listing as a partner club on maisonanversa.com',
    'Quarterly update and a look behind the scenes',
    'Invitations to Maison Anversa events',
    'Priority access to future collections',
    'The option of a joint event at your club',
] as const;

const CRITERIA = [
    'A refined setting that feels like a private club, not a sports hall',
    'A community of members who value quality and experience',
    'A location in a European city with an active padel culture',
    'A willingness to present the Maison Anversa identity correctly and consistently',
    'Club management that believes in a long-term partnership',
    'At least 4 courts and an active membership of 200+',
] as const;

const PROCESS_STEPS = [
    {
        num: '01',
        title: 'Request',
        desc: "Fill in the form with your club's details.",
    },
    {
        num: '02',
        title: 'Introduction',
        desc: 'A personal conversation with Yusuf Savran about the concept and the possibilities.',
    },
    {
        num: '03',
        title: 'Proposal',
        desc: 'A Club Corner proposal tailored to your club.',
    },
    {
        num: '04',
        title: 'Launch',
        desc: 'Your Club Corner opens, with full support from Maison Anversa.',
    },
] as const;

const fieldClassName =
    'border border-gold/20 bg-cream2 px-4 py-3 font-serif text-base text-choc outline-none transition-colors placeholder:text-stone focus:border-gold2';

type CornerClub = {
    city: string;
    country: string;
    status: string;
    status_label?: string;
};

type CornerFormOptions = {
    court_options: string[];
    format_options: string[];
};

export default function Corner({
    clubs = [],
    cornerFormOptions,
}: {
    clubs?: CornerClub[];
    cornerFormOptions: CornerFormOptions;
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const contactRef = useRef<HTMLDivElement>(null);

    function scrollToForm() {
        contactRef.current?.scrollIntoView({ behavior: 'smooth' });
    }

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow="Maison Anversa"
                title={
                    <>
                        Club <em>Corner</em>
                    </>
                }
                subtitle={t(
                    "Maison Anversa's partner programme for padel clubs.",
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <div className="grid items-center gap-16 md:grid-cols-2 md:gap-20">
                        <Reveal>
                            <Eyebrow tone="gold2">
                                {t('What is Club Corner?')}
                            </Eyebrow>
                            <GoldRule />
                            <h2 className="mb-6 font-serif text-[clamp(28px,3.5vw,48px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                                {t('A')} <em>{t('home')}</em>{' '}
                                {t('for Maison Anversa in your club')}
                            </h2>
                            <p className="mb-5 text-base leading-[1.9] text-choc3">
                                {t(
                                    "Club Corner is Maison Anversa's partner programme for padel clubs. Selected clubs across Europe create their own Maison Anversa corner: a carefully designed space where members can discover, hold and buy Heritage No.001.",
                                )}
                            </p>
                            <p className="mb-5 text-base leading-[1.9] text-choc3">
                                {t(
                                    'It is not an ordinary shop. It is a space to experience, where members feel the racket first and become part of the Maison Anversa story.',
                                )}
                            </p>
                            <p className="text-base leading-[1.9] text-choc3">
                                {t(
                                    'We choose our partners carefully. Not every club is right for Maison Anversa, and not every club would want to be. Club Corner is for clubs that share the same values: quality over quantity, community over commerce.',
                                )}
                            </p>
                        </Reveal>

                        <Reveal className="relative aspect-4/5 overflow-hidden">
                            <PlaceholderImage
                                asset="club-corner-impressie"
                                ratio={null}
                                alt={t('Club Corner')}
                                captioned={false}
                                className="absolute inset-0 h-full w-full [&_img]:h-full [&_img]:w-full [&_img]:object-cover [&_img]:object-center"
                            />
                        </Reveal>
                    </div>
                </Wrap>
            </Section>

            <Section tone="choc2">
                <Wrap>
                    <Eyebrow>{t('The Club Corner Package')}</Eyebrow>
                    <GoldRule />
                    <h2 className="mb-3 font-serif text-[clamp(28px,3.5vw,44px)] leading-[1.1] font-medium [&_em]:text-gold [&_em]:italic">
                        {t('What your club')} <em>{t('receives')}</em>
                    </h2>
                    <p className="mb-14 max-w-140 text-base leading-[1.85] text-sand">
                        {t(
                            'Every Club Corner partner receives a complete package to set up the Maison Anversa experience correctly. Everything is designed for consistency, so every Club Corner in Europe offers the same premium experience.',
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
                            {t('Club Corner Formats')}
                        </Eyebrow>
                        <GoldRule center className="mx-auto" />
                        <h2 className="mb-4 font-serif text-[clamp(28px,3.5vw,44px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                            {t('Choose your')} <em>{t('format')}</em>
                        </h2>
                        <p className="text-base leading-[1.85] text-choc3">
                            {t(
                                'Maison Anversa offers two Club Corner formats, depending on the size of your club and your ambition as a partner.',
                            )}
                        </p>
                    </Reveal>

                    <div className="mb-16 grid gap-0.5 bg-sand md:grid-cols-2">
                        <Reveal className="bg-cream px-10 py-12">
                            <span className="mb-4 block font-sans text-[9px] font-medium tracking-[0.3em] text-gold2 uppercase">
                                {t('Format A')}
                            </span>
                            <h3 className="mb-4 font-serif text-[28px] leading-[1.2] font-medium text-choc">
                                {t('Heritage Corner')}
                            </h3>
                            <p className="mb-6 text-[15px] leading-[1.85] text-choc3">
                                {t(
                                    'The entry format for clubs that want to get to know Maison Anversa. Compact, elegant and impactful.',
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
                                {t('Format B')}
                            </span>
                            <h3 className="mb-4 font-serif text-[28px] leading-[1.2] font-medium text-choc">
                                {t('Founding Club Corner')}
                            </h3>
                            <p className="mb-6 text-[15px] leading-[1.85] text-choc3">
                                {t(
                                    'The premium partnership for clubs that want to play a central role in the Maison Anversa network.',
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
                            {t('Request a Partnership Conversation →')}
                        </MaisonButton>
                    </div>
                </Wrap>
            </Section>

            <Section tone="cream2">
                <Wrap>
                    <div className="grid gap-16 md:grid-cols-2 md:gap-20">
                        <Reveal>
                            <Eyebrow tone="gold2">
                                {t('Who qualifies?')}
                            </Eyebrow>
                            <GoldRule />
                            <h2 className="mb-6 font-serif text-[clamp(26px,3vw,40px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                                {t('Not every club.')}
                                <br />
                                {t('The')} <em>{t('right')}</em> {t('club.')}
                            </h2>
                            <p className="mb-5 text-base leading-[1.85] text-choc3">
                                {t(
                                    'Maison Anversa selects Club Corner partners first and foremost on shared values, not on revenue. We are looking for clubs that understand that padel is more than a sport.',
                                )}
                            </p>
                            <p className="text-base leading-[1.85] text-choc3">
                                {t(
                                    'If you recognise these criteria in your club, we would be glad to hear from you.',
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
                            '"Club Corner is not an ordinary point of sale. It is an invitation to become part of something that has only just begun."',
                        )}
                    </blockquote>
                    <cite className="font-sans text-[10px] tracking-[0.25em] text-gold uppercase not-italic">
                        {t('— Yusuf Savran, Founder Maison Anversa')}
                    </cite>
                </Wrap>
            </Section>

            <Section tone="dark">
                <Wrap>
                    <div className="mb-12 grid items-end gap-10 md:grid-cols-[1fr_2fr] md:gap-15">
                        <Reveal>
                            <Eyebrow>{t('The Network')}</Eyebrow>
                            <h2 className="font-serif text-[clamp(26px,3vw,40px)] leading-[1.1] font-medium text-cream [&_em]:text-gold [&_em]:italic">
                                {t('Our Club')} <em>{t('Corners')}</em>
                            </h2>
                        </Reveal>
                        <Reveal>
                            <p className="text-base leading-[1.85] text-sand">
                                {t(
                                    'The first Club Corners will open in selected cities in Belgium, the Netherlands and France. Will your club be the first in your city?',
                                )}
                            </p>
                        </Reveal>
                    </div>

                    <div className="grid gap-0.5 sm:grid-cols-2 lg:grid-cols-3">
                        {clubs.map((club) => (
                            <Reveal
                                key={`${club.country}-${club.city}`}
                                className="border border-gold/10 bg-white/3 px-7 py-8"
                            >
                                <span className="mb-2 block font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                                    {t(club.country)}
                                </span>
                                <div className="mb-2 font-serif text-2xl font-medium text-cream">
                                    {t(club.city)}
                                </div>
                                <div className="font-sans text-[10px] tracking-[0.15em] text-gold uppercase">
                                    {t(club.status_label ?? 'OPEN')}
                                </div>
                            </Reveal>
                        ))}

                        <Reveal className="border border-gold/10 bg-white/3 px-7 py-8">
                            <button
                                type="button"
                                onClick={scrollToForm}
                                className="w-full cursor-pointer text-left"
                            >
                                <span className="mb-2 block font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                                    {t('Your city?')}
                                </span>
                                <div className="mb-2 font-serif text-2xl font-medium text-cream">
                                    {t('Get in touch')}
                                </div>
                                <div className="font-sans text-[10px] tracking-[0.15em] text-gold uppercase">
                                    →
                                </div>
                            </button>
                        </Reveal>
                    </div>
                </Wrap>
            </Section>

            <Section tone="cream" id="cc-contact-form">
                <div ref={contactRef}>
                    <Wrap>
                        <div className="grid gap-16 md:grid-cols-2 md:gap-20">
                            <div>
                                <Eyebrow tone="gold2">
                                    {t('Partnership Request')}
                                </Eyebrow>
                                <GoldRule />
                                <h2 className="mb-5 font-serif text-4xl font-medium [&_em]:text-gold2 [&_em]:italic">
                                    {t("Let's")}
                                    {locale === 'fr' ? '' : ' '}
                                    <em>{t('talk')}</em>
                                </h2>
                                <p className="mb-8 text-base leading-[1.85] text-choc3">
                                    {t(
                                        'Send us a message and we will contact you personally within 48 hours. No automated replies, but a real conversation about what a partnership could mean for your club.',
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
                                                <h3 className="mb-1 font-serif text-lg font-medium text-choc">
                                                    {t(step.title)}
                                                </h3>
                                                <p className="text-[14px] leading-[1.7] text-choc3">
                                                    {t(step.desc)}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <CornerPartnershipForm
                                cornerFormOptions={cornerFormOptions}
                            />
                        </div>
                    </Wrap>
                </div>
            </Section>
        </>
    );
}

function CornerPartnershipForm({
    cornerFormOptions,
}: {
    cornerFormOptions: CornerFormOptions;
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const form = useForm({
        club_name: '',
        name: '',
        email: '',
        location: '',
        courts: cornerFormOptions.court_options[0] ?? '',
        format: cornerFormOptions.format_options[0] ?? '',
        message: '',
        website: '',
    });

    function onSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(cornerInquire.url(locale), {
            preserveScroll: true,
        });
    }

    if (form.recentlySuccessful) {
        return (
            <SuccessPanel
                title={t('Request received.')}
                icon="✓"
                className="rounded border border-gold/15 bg-cream2 px-8 py-10 text-left"
            >
                <p>{t('We will personally contact you within 48 hours.')}</p>
            </SuccessPanel>
        );
    }

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-4">
            <input
                type="text"
                name="website"
                tabIndex={-1}
                autoComplete="off"
                className="hidden"
                value={form.data.website}
                onChange={(event) =>
                    form.setData('website', event.target.value)
                }
            />

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-club-name"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Club name')}
                </label>
                <input
                    id="corner-club-name"
                    name="club_name"
                    type="text"
                    required
                    value={form.data.club_name}
                    onChange={(event) =>
                        form.setData('club_name', event.target.value)
                    }
                    placeholder={t('Name of your padel club')}
                    className={fieldClassName}
                />
                {form.errors.club_name && (
                    <p role="alert" className="text-[13px] text-choc3">
                        {form.errors.club_name}
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-contact"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Contact person')}
                </label>
                <input
                    id="corner-contact"
                    name="name"
                    type="text"
                    required
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    placeholder={t('Your name and role')}
                    className={fieldClassName}
                />
                {form.errors.name && (
                    <p role="alert" className="text-[13px] text-choc3">
                        {form.errors.name}
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-email"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Email address')}
                </label>
                <input
                    id="corner-email"
                    name="email"
                    type="email"
                    required
                    value={form.data.email}
                    onChange={(event) =>
                        form.setData('email', event.target.value)
                    }
                    placeholder={t('your@emailaddress.be')}
                    className={fieldClassName}
                />
                {form.errors.email && (
                    <p role="alert" className="text-[13px] text-choc3">
                        {form.errors.email}
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-location"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('City & Country')}
                </label>
                <input
                    id="corner-location"
                    name="location"
                    type="text"
                    required
                    value={form.data.location}
                    onChange={(event) =>
                        form.setData('location', event.target.value)
                    }
                    placeholder={
                        locale === 'nl'
                            ? t('City, country')
                            : t('Antwerp, Belgium')
                    }
                    className={fieldClassName}
                />
                {form.errors.location && (
                    <p role="alert" className="text-[13px] text-choc3">
                        {form.errors.location}
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-1.5">
                <label
                    htmlFor="corner-courts"
                    className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase"
                >
                    {t('Number of courts')}
                </label>
                <select
                    id="corner-courts"
                    name="courts"
                    required
                    value={form.data.courts}
                    onChange={(event) =>
                        form.setData('courts', event.target.value)
                    }
                    className={fieldClassName}
                >
                    {cornerFormOptions.court_options.map((option) => (
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
                    {t('Desired format')}
                </label>
                <select
                    id="corner-format"
                    name="format"
                    required
                    value={form.data.format}
                    onChange={(event) =>
                        form.setData('format', event.target.value)
                    }
                    className={fieldClassName}
                >
                    {cornerFormOptions.format_options.map((option) => (
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
                    {t('Tell us about your club')}
                </label>
                <textarea
                    id="corner-about"
                    name="message"
                    required
                    value={form.data.message}
                    onChange={(event) =>
                        form.setData('message', event.target.value)
                    }
                    placeholder={t(
                        'Briefly describe the atmosphere and identity of your club...',
                    )}
                    className={`${fieldClassName} min-h-25 resize-y`}
                />
                {form.errors.message && (
                    <p role="alert" className="text-[13px] text-choc3">
                        {form.errors.message}
                    </p>
                )}
            </div>

            <MaisonButton
                type="submit"
                variant="gold"
                block
                disabled={form.processing}
            >
                {form.processing
                    ? t('Working...')
                    : t('Send Partnership Request')}
            </MaisonButton>
        </form>
    );
}
