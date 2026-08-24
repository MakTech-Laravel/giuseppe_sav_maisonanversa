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
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import journalRoutes from '@/routes/admin/journal';

type LocaleCopy = {
    title: string;
    excerpt: string;
    body: string;
    category: string;
    date_label: string;
};

type TranslationStatus = {
    title: boolean;
    excerpt: boolean;
    body: boolean;
    category: boolean;
    date_label: boolean;
};

type JournalLocale = 'nl' | 'en' | 'fr';

interface JournalTranslationsDialogProps {
    articleId: string;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

function emptyLocaleCopy(): LocaleCopy {
    return {
        title: '',
        excerpt: '',
        body: '',
        category: '',
        date_label: '',
    };
}

function initialFormData(translations: Record<string, LocaleCopy>) {
    return {
        nl: { ...emptyLocaleCopy(), ...translations.nl },
        en: { ...emptyLocaleCopy(), ...translations.en },
        fr: { ...emptyLocaleCopy(), ...translations.fr },
    };
}

export function JournalTranslationsDialog({
    articleId,
    locales,
    translations,
    translationStatus,
}: JournalTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<JournalLocale>('nl');

    const form = useForm(
        journalRoutes.translations.update({
            locale,
            article: articleId,
        }),
        initialFormData(translations),
    );

    function handleOpenChange(nextOpen: boolean) {
        if (nextOpen) {
            setActiveLocale((locales[0] as JournalLocale | undefined) ?? 'nl');
            form.setData(initialFormData(translations));
        }

        setOpen(nextOpen);
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function retranslate(targetLocale?: JournalLocale) {
        setTranslating(true);
        router.post(
            journalRoutes.translate({
                locale,
                article: articleId,
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

        return (
            !status?.title ||
            !status?.excerpt ||
            !status?.body ||
            !status?.category ||
            !status?.date_label
        );
    });

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button variant="outline" className="w-full">
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent
                className="admin-kit max-h-[90vh] overflow-y-auto border-border bg-card text-card-foreground shadow-[0_12px_40px_rgba(41,28,24,0.55)] sm:max-w-4xl"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>{t('Vertalingen')}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via journal bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations ? (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla het artikel opnieuw op of gebruik DeepL om ze te genereren.',
                            )}
                        </AlertDescription>
                    </Alert>
                ) : null}

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
                                setActiveLocale(code as JournalLocale)
                            }
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-title`}>
                            {t('Titel')}
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
                        <Label htmlFor={`${activeLocale}-excerpt`}>
                            {t('Excerpt')}
                        </Label>
                        <Textarea
                            id={`${activeLocale}-excerpt`}
                            value={form.data[activeLocale]?.excerpt ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.excerpt`,
                                    event.target.value,
                                )
                            }
                            className="min-h-24 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.excerpt` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-body`}>
                            {t('Body')}
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
                            className="min-h-48 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.body` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-category`}>
                                {t('Categorie')}
                            </Label>
                            <Input
                                id={`${activeLocale}-category`}
                                value={form.data[activeLocale]?.category ?? ''}
                                onChange={(event) =>
                                    form.setData(
                                        `${activeLocale}.category`,
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={
                                    form.errors[
                                        `${activeLocale}.category` as keyof typeof form.errors
                                    ]
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-date_label`}>
                                {t('Datumlabel')}
                            </Label>
                            <Input
                                id={`${activeLocale}-date_label`}
                                value={form.data[activeLocale]?.date_label ?? ''}
                                onChange={(event) =>
                                    form.setData(
                                        `${activeLocale}.date_label`,
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={
                                    form.errors[
                                        `${activeLocale}.date_label` as keyof typeof form.errors
                                    ]
                                }
                            />
                        </div>
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
