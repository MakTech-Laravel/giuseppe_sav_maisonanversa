import { router, useForm } from '@inertiajs/react';
import { Languages, Loader2, RefreshCw, TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
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
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import courtsRoutes from '@/routes/admin/courts';

type LocaleCopy = {
    title: string;
    body: string;
    location: string;
};

type TranslationStatus = {
    title: boolean;
    body: boolean;
    location: boolean;
};

type CourtLocale = 'nl' | 'en' | 'fr';

interface CourtTranslationsDialogProps {
    courtId: string;
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
            title: translations.nl?.title ?? '',
            body: translations.nl?.body ?? '',
            location: translations.nl?.location ?? '',
        },
        en: {
            title: translations.en?.title ?? '',
            body: translations.en?.body ?? '',
            location: translations.en?.location ?? '',
        },
        fr: {
            title: translations.fr?.title ?? '',
            body: translations.fr?.body ?? '',
            location: translations.fr?.location ?? '',
        },
    };
}

export function CourtTranslationsDialog({
    courtId,
    locales,
    translations,
    translationStatus,
}: CourtTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<CourtLocale>('nl');

    const form = useForm(
        courtsRoutes.translations.update({
            locale,
            court: Number(courtId),
        }),
        initialFormData(translations),
    );

    useEffect(() => {
        if (! open) {
            return;
        }

        setActiveLocale((locales[0] as CourtLocale | undefined) ?? 'nl');
        form.setData(initialFormData(translations));
    }, [open, courtId, translations, locales]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function retranslate(targetLocale?: CourtLocale) {
        setTranslating(true);
        router.post(
            courtsRoutes.translate({ locale, court: Number(courtId) }).url,
            targetLocale ? { target_locale: targetLocale } : {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const hasPendingTranslations = locales.some((code) => {
        const status = translationStatus[code];

        return Object.values(status ?? {}).some((complete) => ! complete);
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="w-full">
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent
                className="admin-kit max-h-[90vh] overflow-y-auto border-border bg-card text-card-foreground shadow-[0_12px_40px_rgba(41,28,24,0.55)] sm:max-w-2xl"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>{t('Vertalingen')}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via Club Corner bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla de Club Corner opnieuw op of gebruik DeepL om ze te genereren.',
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
                            onClick={() => setActiveLocale(code as CourtLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-title`}>
                            {t('Titel')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Input
                            id={`${activeLocale}-title`}
                            value={form.data[activeLocale]?.title ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.title`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.title` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-body`}>
                            {t('Beschrijving')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-body`}
                            value={form.data[activeLocale]?.body ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.body`,
                                    event.target.value,
                                )
                            }
                            className="min-h-28 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.body` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-location`}>
                            {t('Locatie')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Input
                            id={`${activeLocale}-location`}
                            value={form.data[activeLocale]?.location ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.location`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.location` as keyof typeof form.errors
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
