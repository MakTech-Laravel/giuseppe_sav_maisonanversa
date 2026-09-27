import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { update } from '@/actions/App/Http/Controllers/Member/RegisterListingController';
import {
    MemberEmptyState,
    MemberPageHeader,
} from '@/components/member/member-ui';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { cn } from '@/lib/utils';

type Listing = {
    number: string;
    full: string;
    initial: string;
    private: string;
    visibility: 'private' | 'initial' | 'full';
    consent: boolean;
};

const OPTIONS = [
    {
        value: 'full',
        title: 'Volledige naam',
        description: 'Uw voor- en achternaam worden publiek getoond.',
        preview: 'full',
    },
    {
        value: 'initial',
        title: 'Voornaam en initiaal',
        description: 'Een discrete publieke vermelding.',
        preview: 'initial',
    },
    {
        value: 'private',
        title: 'Privé',
        description: 'Alleen uw nummer wordt getoond. Dit is de standaard.',
        preview: 'private',
    },
] as const;

export default function RegisterListing({ listing }: { listing: Listing | null }) {
    const { t } = useTranslation();
    const [visibility, setVisibility] = useState(listing?.visibility ?? 'private');
    const [consent, setConsent] = useState(listing?.consent ?? false);
    const needsConsent = visibility !== 'private';

    if (listing === null) {
        return (
            <>
                <Head title={t('Uw registervermelding')} />
                <MemberPageHeader title={t('Uw registervermelding')} />
                <MemberEmptyState
                    title={t('Nog geen editie toegewezen')}
                    description={t(
                        'U bent lid van de Founding Circle, maar er is nog geen Heritage-editie aan uw account gekoppeld. Neem contact op met het team voor meer informatie.',
                    )}
                />
            </>
        );
    }

    return (
        <>
            <Head title={t('Uw registervermelding')} />
            <MemberPageHeader
                eyebrow={t('Founding Circle · lid nr. {{number}}', {
                    number: listing.number,
                })}
                title={t('Uw registervermelding')}
                description={t(
                    'Uw lidmaatschap staat in elk geval in het officiële archief. Kies hoe uw plaats verschijnt in het publieke Founding Circle-register.',
                )}
            />

            <Form
                {...update.form(wayfinderLocale())}
                className="space-y-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="space-y-3">
                            {OPTIONS.map((option) => {
                                const selected = visibility === option.value;

                                return (
                                    <label
                                        key={option.value}
                                        className={cn(
                                            'flex cursor-pointer items-center justify-between gap-4 border px-4 py-4',
                                            selected
                                                ? 'border-gold bg-cream2'
                                                : 'border-gold/25 bg-transparent',
                                        )}
                                    >
                                        <span className="flex items-start gap-3">
                                            <input
                                                type="radio"
                                                name="visibility"
                                                value={option.value}
                                                checked={selected}
                                                onChange={() =>
                                                    setVisibility(option.value)
                                                }
                                                className="mt-1"
                                            />
                                            <span>
                                                <span className="block font-sans text-[11px] tracking-[0.16em] text-cream uppercase">
                                                    {t(option.title)}
                                                </span>
                                                <span className="mt-1 block text-sm text-sand">
                                                    {t(option.description)}
                                                </span>
                                            </span>
                                        </span>
                                        <span className="hidden text-right font-serif text-sm text-gold sm:block">
                                            {listing[option.preview]}
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                        {errors.visibility && (
                            <p className="text-sm text-red-300">{errors.visibility}</p>
                        )}

                        {needsConsent && (
                            <label className="flex items-start gap-3 text-sm text-sand">
                                <input
                                    type="checkbox"
                                    name="consent"
                                    value="1"
                                    checked={consent}
                                    onChange={(event) =>
                                        setConsent(event.target.checked)
                                    }
                                    className="mt-1"
                                />
                                <span>
                                    {t(
                                        'Ik ga ermee akkoord dat de naam hierboven wordt gepubliceerd in het publieke register op maisonanversa.com. Ik kan dit op elk moment wijzigen of intrekken.',
                                    )}
                                </span>
                            </label>
                        )}
                        {errors.consent && (
                            <p className="text-sm text-red-300">{errors.consent}</p>
                        )}

                        <div className="flex flex-wrap items-center gap-4">
                            <button
                                type="submit"
                                disabled={processing || (needsConsent && !consent)}
                                className="min-h-11 bg-gold px-5 font-sans text-[11px] tracking-[0.16em] text-choc uppercase disabled:opacity-50"
                            >
                                {t('Vermelding opslaan')}
                            </button>
                            <p className="text-sm text-sand">
                                {t('Wijzigingen verschijnen meteen in het register.')}
                            </p>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
