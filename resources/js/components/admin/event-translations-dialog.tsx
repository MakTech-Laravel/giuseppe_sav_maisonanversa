import { router, useForm } from '@inertiajs/react';
import { Languages, Loader2, RefreshCw } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
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
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import eventsRoutes from '@/routes/admin/events';

type LocaleCopy = {
    title: string;
    description: string;
    location: string;
};

type TranslationStatus = {
    title: boolean;
    description: boolean;
    location: boolean;
};

interface EventTranslationsDialogProps {
    eventId: string;
    locales: string[];
    defaultLocale: string;
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

export function EventTranslationsDialog({
    eventId,
    locales,
    defaultLocale,
    translations,
    translationStatus,
}: EventTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<string>(
        locales.find((code) => code !== defaultLocale) ?? 'en',
    );

    const form = useForm(
        eventsRoutes.translations.update({
            locale,
            event: eventId,
        }),
        {
            en: {
                title: translations.en?.title ?? '',
                description: translations.en?.description ?? '',
                location: translations.en?.location ?? '',
            },
            fr: {
                title: translations.fr?.title ?? '',
                description: translations.fr?.description ?? '',
                location: translations.fr?.location ?? '',
            },
        },
    );

    useEffect(() => {
        if (! open) {
            return;
        }

        form.setData({
            en: {
                title: translations.en?.title ?? '',
                description: translations.en?.description ?? '',
                location: translations.en?.location ?? '',
            },
            fr: {
                title: translations.fr?.title ?? '',
                description: translations.fr?.description ?? '',
                location: translations.fr?.location ?? '',
            },
        });
    }, [open, translations]);

    function localeFieldValue(
        field: keyof LocaleCopy,
        targetLocale: string,
    ): string {
        if (targetLocale === defaultLocale) {
            return translations[defaultLocale]?.[field] ?? '';
        }

        return (
            form.data[targetLocale as 'en' | 'fr']?.[field] ??
            translations[targetLocale]?.[field] ??
            ''
        );
    }

    function setLocaleField(
        targetLocale: 'en' | 'fr',
        field: keyof LocaleCopy,
        value: string,
    ) {
        form.setData(targetLocale, {
            ...form.data[targetLocale],
            [field]: value,
        });
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function retranslate() {
        setTranslating(true);
        router.post(
            eventsRoutes.translate({ locale, event: eventId }).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const pendingEn =
        !translationStatus.en?.title ||
        !translationStatus.en?.description ||
        !translationStatus.en?.location;
    const pendingFr =
        !translationStatus.fr?.title ||
        !translationStatus.fr?.description ||
        !translationStatus.fr?.location;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="w-full">
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{t('Vertalingen')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Nederlands is de bron. Engels en Frans kunnen handmatig worden aangepast of opnieuw via DeepL worden gegenereerd.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {(pendingEn || pendingFr) && (
                    <p className="rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                        {t(
                            'Sommige vertalingen ontbreken nog. Sla het evenement opnieuw op of gebruik DeepL om ze te genereren.',
                        )}
                    </p>
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
                            onClick={() => setActiveLocale(code)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                {activeLocale === defaultLocale ? (
                    <div className="space-y-4">
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Broninhoud (alleen-lezen). Bewerk via Evenement bewerken.',
                            )}
                        </p>
                        <ReadOnlyField
                            label={t('Titel')}
                            value={translations[defaultLocale]?.title ?? ''}
                        />
                        <ReadOnlyField
                            label={t('Beschrijving')}
                            value={
                                translations[defaultLocale]?.description ?? ''
                            }
                        />
                        <ReadOnlyField
                            label={t('Locatie')}
                            value={translations[defaultLocale]?.location ?? ''}
                        />
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-title`}>
                                {t('Titel')} ({LOCALE_LABELS[activeLocale]})
                            </Label>
                            <Input
                                id={`${activeLocale}-title`}
                                value={localeFieldValue('title', activeLocale)}
                                onChange={(event) =>
                                    setLocaleField(
                                        activeLocale as 'en' | 'fr',
                                        'title',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors[
                                `${activeLocale}.title` as keyof typeof form.errors
                            ] ? (
                                <p className="text-sm text-destructive">
                                    {
                                        form.errors[
                                            `${activeLocale}.title` as keyof typeof form.errors
                                        ]
                                    }
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-description`}>
                                {t('Beschrijving')} (
                                {LOCALE_LABELS[activeLocale]})
                            </Label>
                            <Textarea
                                id={`${activeLocale}-description`}
                                value={localeFieldValue(
                                    'description',
                                    activeLocale,
                                )}
                                onChange={(event) =>
                                    setLocaleField(
                                        activeLocale as 'en' | 'fr',
                                        'description',
                                        event.target.value,
                                    )
                                }
                                className="min-h-28 resize-y"
                            />
                            {form.errors[
                                `${activeLocale}.description` as keyof typeof form.errors
                            ] ? (
                                <p className="text-sm text-destructive">
                                    {
                                        form.errors[
                                            `${activeLocale}.description` as keyof typeof form.errors
                                        ]
                                    }
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-location`}>
                                {t('Locatie')} ({LOCALE_LABELS[activeLocale]})
                            </Label>
                            <Input
                                id={`${activeLocale}-location`}
                                value={localeFieldValue(
                                    'location',
                                    activeLocale,
                                )}
                                onChange={(event) =>
                                    setLocaleField(
                                        activeLocale as 'en' | 'fr',
                                        'location',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors[
                                `${activeLocale}.location` as keyof typeof form.errors
                            ] ? (
                                <p className="text-sm text-destructive">
                                    {
                                        form.errors[
                                            `${activeLocale}.location` as keyof typeof form.errors
                                        ]
                                    }
                                </p>
                            ) : null}
                        </div>

                        <DialogFooter className="gap-2 sm:justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={translating || form.processing}
                                onClick={retranslate}
                            >
                                {translating ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="h-4 w-4" />
                                )}
                                {t('Opnieuw vertalen met DeepL')}
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : null}
                                {t('Vertalingen opslaan')}
                            </Button>
                        </DialogFooter>
                    </form>
                )}

                {activeLocale === defaultLocale ? (
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={translating}
                            onClick={retranslate}
                        >
                            {translating ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="h-4 w-4" />
                            )}
                            {t('Opnieuw vertalen met DeepL')}
                        </Button>
                    </DialogFooter>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}

function ReadOnlyField({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            <div
                className={cn(
                    'min-h-10 rounded-md border bg-muted/30 px-3 py-2 text-sm whitespace-pre-wrap',
                )}
            >
                {value}
            </div>
        </div>
    );
}
