import { router, useForm } from '@inertiajs/react';
import { Languages, Loader2, RefreshCw, TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { STORY_SECTIONS, STORY_TRANSLATED_FIELDS } from '@/components/admin/story-page-fields';
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
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import storyPage from '@/routes/admin/story-page';

type LocaleCopy = Record<string, string>;

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

type StoryLocale = 'nl' | 'en' | 'fr';

function initialFormData(translations: Record<string, LocaleCopy>) {
    const data: Record<StoryLocale, LocaleCopy> = {
        nl: {},
        en: {},
        fr: {},
    };

    (['nl', 'en', 'fr'] as const).forEach((locale) => {
        STORY_TRANSLATED_FIELDS.forEach((field) => {
            data[locale][field.key] = translations[locale]?.[field.key] ?? '';
        });
    });

    return data;
}

export function StoryPageTranslationsDialog({
    locales,
    translations,
    translationStatus,
}: {
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, Record<string, boolean>>;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [wasOpen, setWasOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<StoryLocale>('nl');

    const form = useForm(
        storyPage.translations.update({ locale }),
        initialFormData(translations),
    );

    if (open !== wasOpen) {
        setWasOpen(open);

        if (open) {
            setActiveLocale((locales[0] as StoryLocale | undefined) ?? 'nl');
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

    function retranslate(targetLocale?: StoryLocale) {
        setTranslating(true);
        router.post(
            storyPage.translate({ locale }).url,
            targetLocale ? { target_locale: targetLocale } : {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const hasPendingTranslations = locales.some((code) => {
        const status = translationStatus[code];

        return STORY_TRANSLATED_FIELDS.some((field) => !status?.[field.key]);
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" className="w-full">
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
                            'Bewerk de Nederlandse bron. DeepL vult Engels en Frans; corrigeer die in Vertalingen.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla het verhaal opnieuw op of gebruik DeepL om ze te genereren.',
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
                            variant={activeLocale === code ? 'default' : 'outline'}
                            onClick={() => setActiveLocale(code as StoryLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {STORY_SECTIONS.map((section) => {
                        const fields = section.fields.filter(
                            (field) => field.translated !== false,
                        );

                        if (fields.length === 0) {
                            return null;
                        }

                        return (
                            <div key={section.key} className="space-y-4">
                                <h3 className="font-medium">{t(section.title)}</h3>
                                {fields.map((field) => (
                                    <div key={field.key} className="space-y-2">
                                        <Label htmlFor={`${activeLocale}-${field.key}`}>
                                            {t(field.label)} ({LOCALE_LABELS[activeLocale]})
                                        </Label>
                                        {field.long ? (
                                            <Textarea
                                                id={`${activeLocale}-${field.key}`}
                                                value={form.data[activeLocale]?.[field.key] ?? ''}
                                                onChange={(event) =>
                                                    form.setData(
                                                        `${activeLocale}.${field.key}`,
                                                        event.target.value,
                                                    )
                                                }
                                                rows={3}
                                            />
                                        ) : (
                                            <Input
                                                id={`${activeLocale}-${field.key}`}
                                                value={form.data[activeLocale]?.[field.key] ?? ''}
                                                onChange={(event) =>
                                                    form.setData(
                                                        `${activeLocale}.${field.key}`,
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        )}
                                        <InputError
                                            message={
                                                form.errors[
                                                    `${activeLocale}.${field.key}` as keyof typeof form.errors
                                                ]
                                            }
                                        />
                                    </div>
                                ))}
                            </div>
                        );
                    })}

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
