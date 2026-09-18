import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { SuccessPanel } from '@/components/maison/ui/success-panel';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';
import { store as heritageLetterStore } from '@/routes/maison/heritage-letter';

type HeritageLetterFormProps = {
    source: 'home' | 'story' | 'modal' | 'waitlist' | 'sold_out';
    showName?: boolean;
    variant?: 'dark' | 'light';
    className?: string;
    /** Called when signup succeeds (e.g. close the newsletter modal). */
    onSuccess?: () => void;
};

export function HeritageLetterForm({
    source,
    showName = false,
    variant = 'dark',
    className,
    onSuccess,
}: HeritageLetterFormProps) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const form = useForm({
        email: '',
        name: '',
        source,
        website: '',
    });

    const inputClass =
        variant === 'dark'
            ? 'min-w-0 flex-1 border border-gold/25 bg-transparent px-4 py-3.5 font-sans text-[13px] text-cream outline-none placeholder:text-stone focus:border-gold'
            : 'min-w-0 flex-1 border border-gold/25 bg-transparent px-4 py-3.5 font-sans text-[13px] text-choc outline-none placeholder:text-stone focus:border-gold';

    if (form.recentlySuccessful && !onSuccess) {
        return (
            <SuccessPanel
                title={t('Welkom bij de Heritage Letter.')}
                icon="✓"
                className={
                    variant === 'dark'
                        ? 'text-left [&_div]:text-sand [&_h3]:text-cream'
                        : 'text-left'
                }
            >
                <p>{t('U ontvangt binnenkort een bevestiging.')}</p>
            </SuccessPanel>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(heritageLetterStore.url(locale), {
                    preserveScroll: true,
                    onSuccess: () => {
                        onSuccess?.();
                    },
                });
            }}
            className={cn('flex flex-col gap-3.5', className)}
        >
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

            {showName && (
                <>
                    <label className="sr-only" htmlFor={`nl-name-${source}`}>
                        {t('Uw naam')}
                    </label>
                    <input
                        id={`nl-name-${source}`}
                        type="text"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        placeholder={t('Uw naam')}
                        className={inputClass}
                    />
                </>
            )}

            <div className="flex flex-col gap-0 sm:flex-row">
                <label className="sr-only" htmlFor={`nl-email-${source}`}>
                    {t('Uw e-mailadres')}
                </label>
                <input
                    id={`nl-email-${source}`}
                    type="email"
                    required
                    value={form.data.email}
                    onChange={(event) =>
                        form.setData('email', event.target.value)
                    }
                    placeholder={t('Uw e-mailadres')}
                    className={inputClass}
                />
                <button
                    type="submit"
                    disabled={form.processing}
                    data-magnetic
                    className="shrink-0 border border-gold bg-gold px-6 py-3.5 font-sans text-[10px] font-medium tracking-[0.25em] text-choc uppercase transition-colors hover:bg-gold2 disabled:opacity-60"
                >
                    {form.processing ? t('Bezig…') : t('Word Lid')}
                </button>
            </div>

            {form.errors.email && (
                <p role="alert" className="text-[13px] text-choc3">
                    {form.errors.email}
                </p>
            )}

            <p className="mt-1 font-sans text-[10px] tracking-[0.08em] text-stone">
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
        </form>
    );
}
