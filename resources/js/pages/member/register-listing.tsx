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
        title: 'Full name',
        description: 'Your first and last name are shown publicly.',
        preview: 'full',
    },
    {
        value: 'initial',
        title: 'First name and initial',
        description: 'A discreet public listing.',
        preview: 'initial',
    },
    {
        value: 'private',
        title: 'Privé',
        description: 'Only your number is shown. This is the default.',
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
                <Head title={t('Your register listing')} />
                <MemberPageHeader title={t('Your register listing')} />
                <MemberEmptyState
                    title={t('No edition assigned yet')}
                    description={t(
                        'You are a Founding Circle member, but no Heritage edition is linked to your account yet. Contact the team for more information.',
                    )}
                />
            </>
        );
    }

    return (
        <>
            <Head title={t('Your register listing')} />
            <MemberPageHeader
                eyebrow={t('Founding Circle · member no. {{number}}', {
                    number: listing.number,
                })}
                title={t('Your register listing')}
                description={t(
                    'Your membership is in the official archive either way. Choose how your place appears in the public Founding Circle register.',
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
                                                className="mt-1 size-4 accent-gold"
                                            />
                                            <span>
                                                <span
                                                    className={cn(
                                                        'block font-sans text-[11px] tracking-[0.16em] uppercase',
                                                        selected
                                                            ? 'text-choc'
                                                            : 'text-cream',
                                                    )}
                                                >
                                                    {t(option.title)}
                                                </span>
                                                <span
                                                    className={cn(
                                                        'mt-1 block text-sm',
                                                        selected
                                                            ? 'text-choc3'
                                                            : 'text-sand',
                                                    )}
                                                >
                                                    {t(option.description)}
                                                </span>
                                            </span>
                                        </span>
                                        <span
                                            className={cn(
                                                'hidden text-right font-serif text-sm sm:block',
                                                selected
                                                    ? 'text-gold2'
                                                    : 'text-gold',
                                            )}
                                        >
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
                            <label className="flex cursor-pointer items-start gap-3 text-sm text-sand">
                                <input
                                    type="checkbox"
                                    name="consent"
                                    value="1"
                                    checked={consent}
                                    onChange={(event) =>
                                        setConsent(event.target.checked)
                                    }
                                    className="mt-1 size-4 accent-gold"
                                />
                                <span>
                                    {t(
                                        'I agree that the name above will be published in the public register on maisonanversa.com. I can change or withdraw this at any time.',
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
                                {t('Save listing')}
                            </button>
                            <p className="text-sm text-sand">
                                {t('Changes appear in the register immediately.')}
                            </p>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
