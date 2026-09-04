import { useForm, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import * as clubRoutes from '@/routes/community/clubs';

type ClubSubmitDialogProps = {
    /** Prefills the name with whatever the member just searched for. */
    defaultName: string;
    defaultSport: string;
};

/**
 * When a venue is missing, members add it themselves. It is stored as pending
 * and cannot be attached to a session until an admin approves it.
 */
export function ClubSubmitDialog({
    defaultName,
    defaultSport,
}: ClubSubmitDialogProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [open, setOpen] = useState(false);

    const form = useForm({
        name: defaultName,
        sports: [defaultSport],
        street: '',
        postal_code: '',
        city: '',
        country: 'BE',
        website: '',
    });

    useEffect(() => {
        if (!open) {
            form.setData('name', defaultName);
            form.setData('sports', [defaultSport]);
        }
        // Resetting while the dialog is open would fight the member's typing.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [defaultName, defaultSport, open]);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post(clubRoutes.store.url(locale), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <button
                    type="button"
                    className="inline-flex cursor-pointer items-center gap-2 font-sans text-[10px] tracking-[0.16em] text-gold2 uppercase underline underline-offset-4 hover:text-choc"
                >
                    <Plus className="size-3.5" aria-hidden="true" />
                    {t('Club niet gevonden? Voeg er een toe')}
                </button>
            </DialogTrigger>

            <DialogContent className="max-h-[85vh] overflow-y-auto border-gold/20 bg-cream sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="font-serif text-2xl font-medium text-choc">
                        {t('Club of corner toevoegen')}
                    </DialogTitle>
                    <DialogDescription className="font-sans text-[11px] tracking-[0.1em] text-stone uppercase">
                        {t(
                            'Wordt zichtbaar na goedkeuring door Maison Anversa.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <Field label={t('Naam')} error={form.errors.name}>
                        <input
                            type="text"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            className={fieldClassName}
                            required
                        />
                    </Field>

                    <Field label={t('Sporten')} error={form.errors.sports}>
                        <div className="flex gap-2">
                            {['padel', 'tennis'].map((sport) => {
                                const active = form.data.sports.includes(sport);

                                return (
                                    <button
                                        key={sport}
                                        type="button"
                                        onClick={() => {
                                            if (
                                                active &&
                                                form.data.sports.length === 1
                                            ) {
                                                return;
                                            }

                                            form.setData(
                                                'sports',
                                                active
                                                    ? form.data.sports.filter(
                                                          (item) =>
                                                              item !== sport,
                                                      )
                                                    : [
                                                          ...form.data.sports,
                                                          sport,
                                                      ],
                                            );
                                        }}
                                        className={cn(
                                            'flex-1 cursor-pointer border px-4 py-2.5 font-sans text-[10px] tracking-[0.16em] uppercase transition-colors',
                                            active
                                                ? 'border-choc bg-choc text-cream'
                                                : 'border-gold/25 bg-cream text-choc hover:border-gold',
                                        )}
                                    >
                                        {t(
                                            sport === 'padel'
                                                ? 'Padel'
                                                : 'Tennis',
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </Field>

                    <Field
                        label={t('Straat en nummer')}
                        error={form.errors.street}
                    >
                        <input
                            type="text"
                            value={form.data.street}
                            onChange={(event) =>
                                form.setData('street', event.target.value)
                            }
                            className={fieldClassName}
                        />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-[8rem_1fr]">
                        <Field
                            label={t('Postcode')}
                            error={form.errors.postal_code}
                        >
                            <input
                                type="text"
                                value={form.data.postal_code}
                                onChange={(event) =>
                                    form.setData(
                                        'postal_code',
                                        event.target.value,
                                    )
                                }
                                className={fieldClassName}
                            />
                        </Field>

                        <Field label={t('Stad')} error={form.errors.city}>
                            <input
                                type="text"
                                value={form.data.city}
                                onChange={(event) =>
                                    form.setData('city', event.target.value)
                                }
                                className={fieldClassName}
                            />
                        </Field>
                    </div>

                    <Field label={t('Website')} error={form.errors.website}>
                        <input
                            type="text"
                            inputMode="url"
                            value={form.data.website}
                            onChange={(event) =>
                                form.setData('website', event.target.value)
                            }
                            placeholder="https://"
                            className={fieldClassName}
                        />
                    </Field>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full cursor-pointer bg-choc px-6 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:opacity-50"
                    >
                        {t('Club indienen')}
                    </button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

const fieldClassName =
    'w-full border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc outline-none transition-colors focus:border-gold2';

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="flex flex-col gap-1.5">
            <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                {label}
            </span>
            {children}
            {error && (
                <span className="font-sans text-[11px] text-red-800">
                    {error}
                </span>
            )}
        </label>
    );
}
