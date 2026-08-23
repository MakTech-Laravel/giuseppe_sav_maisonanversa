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

type TranslatableColumn = keyof LocaleCopy;

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
    const [saving, setSaving] = useState(false);
    const [translatingField, setTranslatingField] = useState<string | null>(
        null,
    );
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
        field: TranslatableColumn,
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
        field: TranslatableColumn,
        value: string,
    ) {
        form.setData(targetLocale, {
            ...form.data[targetLocale],
            [field]: value,
        });
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        if (activeLocale === defaultLocale) {
            return;
        }

        const targetLocale = activeLocale as 'en' | 'fr';
        const copy = form.data[targetLocale];

        router.put(
            eventsRoutes.translations.update({ locale, event: eventId }).url,
            {
                target_locale: targetLocale,
                title: copy.title,
                description: copy.description,
                location: copy.location,
            },
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                onSuccess: () => setOpen(false),
            },
        );
    }

    function translateField(
        targetLocale: string,
        column: TranslatableColumn,
    ) {
        const fieldKey = `${targetLocale}:${column}`;
        setTranslatingField(fieldKey);

        router.post(
            eventsRoutes.translateColumn({ locale, event: eventId }).url,
            {
                target_locale: targetLocale,
                column,
            },
            {
                preserveScroll: true,
                onFinish: () => setTranslatingField(null),
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
                            'Nederlands is de bron. Engels en Frans kunnen handmatig worden aangepast of per veld via DeepL worden gegenereerd.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {(pendingEn || pendingFr) && (
                    <p className="rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                        {t(
                            'Sommige vertalingen ontbreken nog. Sla het evenement opnieuw op of gebruik DeepL per veld om ze te genereren.',
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
                        <TranslatableField
                            id={`${activeLocale}-title`}
                            label={t('Titel')}
                            localeLabel={LOCALE_LABELS[activeLocale]}
                            value={localeFieldValue('title', activeLocale)}
                            onChange={(value) =>
                                setLocaleField(
                                    activeLocale as 'en' | 'fr',
                                    'title',
                                    value,
                                )
                            }
                            error={
                                form.errors.title as string | undefined
                            }
                            onTranslate={() =>
                                translateField(activeLocale, 'title')
                            }
                            translating={
                                translatingField === `${activeLocale}:title`
                            }
                        />
                        <TranslatableField
                            id={`${activeLocale}-description`}
                            label={t('Beschrijving')}
                            localeLabel={LOCALE_LABELS[activeLocale]}
                            value={localeFieldValue(
                                'description',
                                activeLocale,
                            )}
                            onChange={(value) =>
                                setLocaleField(
                                    activeLocale as 'en' | 'fr',
                                    'description',
                                    value,
                                )
                            }
                            error={form.errors.description as string | undefined}
                            multiline
                            onTranslate={() =>
                                translateField(activeLocale, 'description')
                            }
                            translating={
                                translatingField ===
                                `${activeLocale}:description`
                            }
                        />
                        <TranslatableField
                            id={`${activeLocale}-location`}
                            label={t('Locatie')}
                            localeLabel={LOCALE_LABELS[activeLocale]}
                            value={localeFieldValue('location', activeLocale)}
                            onChange={(value) =>
                                setLocaleField(
                                    activeLocale as 'en' | 'fr',
                                    'location',
                                    value,
                                )
                            }
                            error={form.errors.location as string | undefined}
                            onTranslate={() =>
                                translateField(activeLocale, 'location')
                            }
                            translating={
                                translatingField === `${activeLocale}:location`
                            }
                        />

                        <DialogFooter>
                            <Button type="submit" disabled={saving}>
                                {saving ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : null}
                                {t('Vertaling opslaan')}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function TranslatableField({
    id,
    label,
    localeLabel,
    value,
    onChange,
    error,
    multiline = false,
    onTranslate,
    translating,
}: {
    id: string;
    label: string;
    localeLabel: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    multiline?: boolean;
    onTranslate: () => void;
    translating: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <Label htmlFor={id}>
                    {label} ({localeLabel})
                </Label>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    disabled={translating}
                    onClick={onTranslate}
                    title={t('Vertaal dit veld met DeepL')}
                >
                    {translating ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <RefreshCw className="h-4 w-4" />
                    )}
                    {t('DeepL')}
                </Button>
            </div>
            {multiline ? (
                <Textarea
                    id={id}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="min-h-28 resize-y"
                />
            ) : (
                <Input
                    id={id}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                />
            )}
            {error ? (
                <p className="text-sm text-destructive">{error}</p>
            ) : null}
        </div>
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
