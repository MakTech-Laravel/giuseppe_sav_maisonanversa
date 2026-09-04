import { router, useForm } from '@inertiajs/react';
import { Languages, Loader2, RefreshCw, TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import partnerClubs from '@/routes/admin/partner-clubs';

type LocaleCopy = {
    city: string;
    country: string;
};

type TranslationStatus = {
    city: boolean;
    country: boolean;
};

type PartnerClubLocale = 'nl' | 'en' | 'fr';

interface PartnerClubTranslationsDialogProps {
    partnerClubId: string;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

function initialFormData(translations: Record<string, LocaleCopy>) {
    return {
        nl: {
            city: translations.nl?.city ?? '',
            country: translations.nl?.country ?? '',
        },
        en: {
            city: translations.en?.city ?? '',
            country: translations.en?.country ?? '',
        },
        fr: {
            city: translations.fr?.city ?? '',
            country: translations.fr?.country ?? '',
        },
    };
}

export function PartnerClubTranslationsDialog({
    partnerClubId,
    locales,
    translations,
    translationStatus,
}: PartnerClubTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [wasOpen, setWasOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<PartnerClubLocale>('nl');

    const form = useForm(
        partnerClubs.translations.update({
            locale,
            partnerClub: Number(partnerClubId),
        }),
        initialFormData(translations),
    );

    // Reset the form to the latest translations each time the dialog opens.
    // Adjusted during render rather than in an effect — see
    // https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes
    if (open !== wasOpen) {
        setWasOpen(open);

        if (open) {
            setActiveLocale(
                (locales[0] as PartnerClubLocale | undefined) ?? 'nl',
            );
            form.setData(initialFormData(translations));
        }
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function retranslate(targetLocale?: PartnerClubLocale) {
        setTranslating(true);
        router.post(
            partnerClubs.translate({
                locale,
                partnerClub: Number(partnerClubId),
            }).url,
            targetLocale ? { target_locale: targetLocale } : {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const hasPendingTranslations = locales.some((code) => {
        const status = translationStatus[code];

        return Object.values(status ?? {}).some((complete) => !complete);
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent
                className="admin-kit max-h-[90vh] overflow-y-auto border-border bg-card text-card-foreground shadow-[0_12px_40px_rgba(41,28,24,0.55)] sm:max-w-lg"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>{t('Vertalingen')}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via de club bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla de club opnieuw op of gebruik DeepL om ze te genereren.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap gap-2">
                    {locales.map((code) => (
                        <Button
                            key={code}
                            type="button"
                            size="sm"
                            variant={
                                activeLocale === code ? 'default' : 'outline'
                            }
                            onClick={() =>
                                setActiveLocale(code as PartnerClubLocale)
                            }
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-city`}>
                            {t('Stad')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Input
                            id={`${activeLocale}-city`}
                            value={form.data[activeLocale]?.city ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.city`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.city` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-country`}>
                            {t('Land')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Input
                            id={`${activeLocale}-country`}
                            value={form.data[activeLocale]?.country ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.country`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.country` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>

                    <DialogFooter className="flex-col gap-2 sm:flex-row sm:justify-between">
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={translating || form.processing}
                                onClick={() => retranslate(activeLocale)}
                            >
                                {translating ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="h-4 w-4" />
                                )}
                                {t('Opnieuw vertalen ({{locale}})', {
                                    locale: LOCALE_LABELS[activeLocale],
                                })}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={translating || form.processing}
                                onClick={() => retranslate()}
                            >
                                {translating ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="h-4 w-4" />
                                )}
                                {t('Alles opnieuw vertalen')}
                            </Button>
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : null}
                            {t('Vertalingen opslaan')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
