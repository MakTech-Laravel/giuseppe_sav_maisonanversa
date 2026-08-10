import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { SuccessPanel } from '@/components/maison/ui/success-panel';

/**
 * The inline Heritage Letter form on the home page. Until Mailchimp is wired,
 * a valid address shows the success panel locally — the same soft landing the
 * prototype used when its form URL was empty.
 */
export function HomeNewsletter() {
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
        <div
            id="nl-home"
            className="grid items-center gap-10 border-t border-gold/15 bg-choc px-8 py-18 text-cream md:grid-cols-2 md:gap-15 md:px-20"
        >
            <div>
                <Eyebrow>{t('De Heritage Letter')}</Eyebrow>
                <h2 className="mt-3 mb-2.5 font-serif text-[clamp(24px,2.8vw,36px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                    {t('Word lid van')}
                    <br />
                    {t('de Heritage Letter')}
                </h2>
                <p className="text-[14px] leading-[1.8] text-sand">
                    {t(
                        'Als eerste verhalen, nieuws en exclusieve uitnodigingen ontvangen van Maison Anversa.',
                    )}
                </p>
            </div>

            <div>
                {sent ? (
                    <SuccessPanel
                        title={t('Welkom bij de Heritage Letter.')}
                        icon="✓"
                        className="text-left [&_div]:text-sand [&_h3]:text-cream"
                    >
                        <p>{t('U ontvangt binnenkort een bevestiging.')}</p>
                    </SuccessPanel>
                ) : (
                    <form
                        onSubmit={onSubmit}
                        className="flex flex-col gap-0 sm:flex-row"
                        noValidate={false}
                    >
                        <label className="sr-only" htmlFor="nl-home-email">
                            {t('Uw e-mailadres')}
                        </label>
                        <input
                            id="nl-home-email"
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
                            {t('Word Lid')}
                        </button>
                    </form>
                )}
                <p className="mt-3 font-sans text-[10px] tracking-[0.08em] text-stone">
                    {t(
                        'Door u in te schrijven gaat u akkoord met onze privacyverklaring.',
                    )}{' '}
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
