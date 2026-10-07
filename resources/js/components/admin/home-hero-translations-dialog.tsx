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
import homeHero from '@/routes/admin/home-hero';

type LocaleCopy = Record<string, string>;

const COLUMNS = [
    ['eyebrow', 'Wenkbrauw'],
    ['title', 'Titel'],
    ['title_accent', 'Titelregel 2'],
    ['tagline', 'Ondertitel'],
    ['counter_line_one', 'Regel 1'],
    ['counter_line_two', 'Regel 2'],
    ['primary_label', 'Primaire knop'],
    ['secondary_label', 'Secundaire knop'],
    ['tertiary_label', 'Tertiaire knop'],
] as const;

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

type HeroLocale = 'nl' | 'en' | 'fr';

function initialFormData(translations: Record<string, LocaleCopy>) {
    const data: Record<HeroLocale, LocaleCopy> = {
        nl: {},
        en: {},
        fr: {},
    };

    (['nl', 'en', 'fr'] as const).forEach((locale) => {
        COLUMNS.forEach(([column]) => {
            data[locale][column] = translations[locale]?.[column] ?? '';
        });
    });

    return data;
}

export function HomeHeroTranslationsDialog({
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
    const [activeLocale, setActiveLocale] = useState<HeroLocale>('nl');

    const form = useForm(
        homeHero.translations.update({ locale }),
        initialFormData(translations),
    );

    if (open !== wasOpen) {
        setWasOpen(open);

        if (open) {
            setActiveLocale((locales[0] as HeroLocale | undefined) ?? 'nl');
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

    function retranslate(targetLocale?: HeroLocale) {
        setTranslating(true);
        router.post(
            homeHero.translate({ locale }).url,
            targetLocale ? { target_locale: targetLocale } : {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const hasPendingTranslations = locales.some((code) => {
        const status = translationStatus[code];

        return COLUMNS.some(([column]) => !status?.[column]);
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
                                'Sommige vertalingen ontbreken nog. Sla de hero opnieuw op of gebruik DeepL om ze te genereren.',
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
                            onClick={() => setActiveLocale(code as HeroLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    {COLUMNS.map(([column, label]) => (
                        <div key={column} className="space-y-2">
                            <Label htmlFor={`${activeLocale}-${column}`}>
                                {t(label)} ({LOCALE_LABELS[activeLocale]})
                            </Label>
                            <Input
                                id={`${activeLocale}-${column}`}
                                value={
                                    form.data[activeLocale]?.[column] ?? ''
                                }
                                onChange={(event) =>
                                    form.setData(
                                        `${activeLocale}.${column}`,
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={
                                    form.errors[
                                        `${activeLocale}.${column}` as keyof typeof form.errors
                                    ]
                                }
                            />
                        </div>
                    ))}

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
