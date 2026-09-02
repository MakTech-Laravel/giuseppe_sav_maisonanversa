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
import dressingItems from '@/routes/admin/dressing-items';

type LocaleCopy = {
    name: string;
    category: string;
    description: string;
};

type TranslationStatus = {
    name: boolean;
    category: boolean;
    description: boolean;
};

type DressingItemLocale = 'nl' | 'en' | 'fr';

interface DressingItemTranslationsDialogProps {
    dressingItemId: string;
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
            name: translations.nl?.name ?? '',
            category: translations.nl?.category ?? '',
            description: translations.nl?.description ?? '',
        },
        en: {
            name: translations.en?.name ?? '',
            category: translations.en?.category ?? '',
            description: translations.en?.description ?? '',
        },
        fr: {
            name: translations.fr?.name ?? '',
            category: translations.fr?.category ?? '',
            description: translations.fr?.description ?? '',
        },
    };
}

export function DressingItemTranslationsDialog({
    dressingItemId,
    locales,
    translations,
    translationStatus,
}: DressingItemTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [wasOpen, setWasOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<DressingItemLocale>('nl');

    const form = useForm(
        dressingItems.translations.update({
            locale,
            dressingItem: Number(dressingItemId),
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
                (locales[0] as DressingItemLocale | undefined) ?? 'nl',
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

    function retranslate(targetLocale?: DressingItemLocale) {
        setTranslating(true);
        router.post(
            dressingItems.translate({
                locale,
                dressingItem: Number(dressingItemId),
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
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via dit item bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla het item opnieuw op of gebruik DeepL om ze te genereren.',
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
                                setActiveLocale(code as DressingItemLocale)
                            }
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-name`}>
                            {t('Naam')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Input
                            id={`${activeLocale}-name`}
                            value={form.data[activeLocale]?.name ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.name`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.name` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-category`}>
                            {t('Categorie')} ({LOCALE_LABELS[activeLocale]})
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
                        <Label htmlFor={`${activeLocale}-description`}>
                            {t('Beschrijving')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-description`}
                            value={form.data[activeLocale]?.description ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.description`,
                                    event.target.value,
                                )
                            }
                            className="min-h-24 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.description` as keyof typeof form.errors
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
