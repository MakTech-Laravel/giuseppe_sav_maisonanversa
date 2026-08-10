import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Section, Wrap } from '@/components/maison/ui/section';
import { SuccessPanel } from '@/components/maison/ui/success-panel';

export default function Story() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead page="story" />

            <PageHero
                eyebrow="Maison Anversa"
                title={
                    <>
                        {t('Ons')} <em>{t('Verhaal')}</em>
                    </>
                }
                subtitle={t(
                    'Waarom wij bestaan. Waar wij vandaan komen. Wat wij bouwen.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <article className="mx-auto max-w-180">
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t(
                                'Maison Anversa werd gesticht op een eenvoudige overtuiging: de wereld heeft geen extra producten nodig. Zij heeft meer betekenis nodig.',
                            )}
                        </p>

                        <h2 className="mt-12 mb-5 font-serif text-[34px] font-medium text-choc [&_em]:text-gold2 [&_em]:italic">
                            <em>Antwerpen</em>
                        </h2>
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t(
                                'Antwerpen heeft ons gevormd. Een stad gebouwd op eeuwen van diamanthandel, Vlaamse Meesters en culturele ambitie. Een stad die altijd verder keek dan haar eigen grenzen, terwijl zij geworteld bleef in haar eigen identiteit.',
                            )}
                        </p>
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t(
                                '"Anvers" is de Franse naam voor Antwerpen. "Anversa" is onze Europese variatie — elegant in het Frans, Italiaans en Engels tegelijk. De naam draagt de stad. De stad draagt het merk.',
                            )}
                        </p>

                        <blockquote className="my-11 border-l-2 border-gold2 py-1 pl-7 font-serif text-[22px] leading-[1.55] text-choc italic">
                            Heritage is not what we inherit. It is what we build
                            for those who follow.
                        </blockquote>

                        <h2 className="mt-12 mb-5 font-serif text-[34px] font-medium text-choc [&_em]:text-gold2 [&_em]:italic">
                            {t('Waarom')} <em>padel</em>?
                        </h2>
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t(
                                'Padel is meer dan een sport. Het is een sociaal ritueel. Een bijeenkomst van mensen die waarden delen, die kwaliteit waarderen, die investeren in ervaringen. Het was het perfecte startpunt voor een merk dat gelooft dat sport en lifestyle hetzelfde zijn.',
                            )}
                        </p>
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t('Padel is het startpunt, niet de bestemming.')}
                        </p>

                        <h2 className="mt-12 mb-5 font-serif text-[34px] font-medium text-choc [&_em]:text-gold2 [&_em]:italic">
                            {t('Wat wij')} <em>{t('bouwen')}</em>
                        </h2>
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t(
                                'Wij bouwen een huis. Niet een sportmerk. Niet een lifestyle label. Een huis met waarden, met verhalen, met producten die mensen bewaren en niet weggooien.',
                            )}
                        </p>
                        <p className="mb-5 text-[17px] leading-[1.9] text-choc3">
                            {t(
                                'Heritage No.001 is ons eerste hoofdstuk. Een padelracket, beperkt tot 100 stuks, vergezeld van een certificaat, een paspoort en een brief — niet omdat het moet, maar omdat wij geloven dat een eerste product ook een eerste hoofdstuk verdient.',
                            )}
                        </p>

                        <h2 className="mt-12 mb-5 font-serif text-[34px] font-medium text-choc [&_em]:text-gold2 [&_em]:italic">
                            {t('De')} <em>{t('Oprichter')}</em>
                        </h2>
                        <div className="my-12 grid gap-10 border border-gold/20 bg-cream2 p-10 md:grid-cols-[1fr_2fr]">
                            <div className="flex aspect-square items-center justify-center bg-choc2">
                                <span className="font-serif text-[40px] font-normal text-gold/20">
                                    YS
                                </span>
                            </div>
                            <div>
                                <h3 className="mb-1 font-serif text-[26px] font-medium text-choc">
                                    {t('Yusuf Savran')}
                                </h3>
                                <span className="mb-4 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                                    {t('Oprichter, Maison Anversa')}
                                </span>
                                <p className="text-[15px] leading-[1.8] text-choc3">
                                    {t(
                                        '[Persoonlijke bio — door Yusuf zelf te schrijven. Achtergrond, connectie met Antwerpen, visie voor Maison Anversa en waarom dit project persoonlijk betekenis heeft.]',
                                    )}
                                </p>
                            </div>
                        </div>
                    </article>
                </Wrap>
            </Section>

            <StoryNewsletter />
        </>
    );
}

function StoryNewsletter() {
    const { t } = useTranslation();
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);

    function onSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!event.currentTarget.reportValidity()) {
            return;
        }

        setSent(true);
    }

    return (
        <div className="grid items-center gap-10 border-t border-gold/15 bg-choc px-8 py-18 text-cream md:grid-cols-2 md:gap-15 md:px-20">
            <div>
                <Eyebrow>Heritage Letter</Eyebrow>
                <h2 className="mt-3 mb-2.5 font-serif text-[clamp(22px,2.5vw,32px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                    {t('Blijf op de hoogte')}
                </h2>
                <p className="text-sm leading-[1.8] text-sand">
                    {t(
                        'Updates over Heritage No.001 en toekomstige collecties.',
                    )}
                </p>
            </div>

            <div>
                {sent ? (
                    <SuccessPanel
                        title={t('Bedankt — welkom bij Maison Anversa.')}
                        icon="✓"
                        className="text-left [&_div]:text-sand [&_h3]:text-cream"
                    />
                ) : (
                    <form
                        onSubmit={onSubmit}
                        className="flex flex-col gap-0 sm:flex-row"
                    >
                        <label className="sr-only" htmlFor="nl-story-email">
                            {t('Uw e-mailadres')}
                        </label>
                        <input
                            id="nl-story-email"
                            type="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder={t('Uw e-mailadres')}
                            className="min-w-0 flex-1 border border-gold/25 bg-transparent px-4 py-3.5 font-sans text-[13px] text-cream outline-none placeholder:text-stone focus:border-gold"
                        />
                        <button
                            type="submit"
                            data-magnetic
                            className="shrink-0 border border-gold bg-gold px-6 py-3.5 font-sans text-[10px] font-medium tracking-[0.25em] text-choc uppercase transition-colors hover:bg-gold2"
                        >
                            {t('Schrijf in')}
                        </button>
                    </form>
                )}
                <p className="mt-3 font-sans text-[10px] tracking-[0.08em] text-stone">
                    {t('Privacyverklaring van toepassing.')}{' '}
                    <MaisonLink
                        to="privacy"
                        className="underline underline-offset-2 hover:text-gold"
                    >
                        {t('Privacybeleid')}
                    </MaisonLink>
                </p>
            </div>
        </div>
    );
}
