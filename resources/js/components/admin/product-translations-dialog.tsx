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
import products from '@/routes/admin/products';

type LocaleCopy = {
    name: string;
    eyebrow: string;
    hero_eyebrow: string;
    hero_subtitle: string;
    description: string;
    expected_delivery_label: string;
};

type TranslationStatus = {
    name: boolean;
    eyebrow: boolean;
    hero_eyebrow: boolean;
    hero_subtitle: boolean;
    description: boolean;
    expected_delivery_label: boolean;
};

type ProductLocale = 'nl' | 'en' | 'fr';

interface ProductTranslationsDialogProps {
    productId: number;
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
        name: '',
        eyebrow: '',
        hero_eyebrow: '',
        hero_subtitle: '',
        description: '',
        expected_delivery_label: '',
    };
}

function initialFormData(translations: Record<string, LocaleCopy>) {
    return {
        nl: { ...emptyLocaleCopy(), ...translations.nl },
        en: { ...emptyLocaleCopy(), ...translations.en },
        fr: { ...emptyLocaleCopy(), ...translations.fr },
    };
}

export function ProductTranslationsDialog({
    productId,
    locales,
    translations,
    translationStatus,
}: ProductTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<ProductLocale>('nl');

    const form = useForm(
        products.translations.update({
            locale,
            product: productId,
        }),
        initialFormData(translations),
    );

    function handleOpenChange(nextOpen: boolean) {
        if (nextOpen) {
            setActiveLocale(
                (locales[0] as ProductLocale | undefined) ?? 'nl',
            );
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

    function retranslate(targetLocale?: ProductLocale) {
        setTranslating(true);
        router.post(
            products.translate({ locale, product: productId }).url,
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
            !status?.name ||
            !status?.eyebrow ||
            !status?.hero_eyebrow ||
            !status?.hero_subtitle ||
            !status?.description ||
            !status?.expected_delivery_label
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
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via product bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla het product opnieuw op of gebruik DeepL om ze te genereren.',
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
                                setActiveLocale(code as ProductLocale)
                            }
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-name`}>
                            {t('Naam')}
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
                        <Label htmlFor={`${activeLocale}-eyebrow`}>
                            {t('Productlabel')}
                        </Label>
                        <Input
                            id={`${activeLocale}-eyebrow`}
                            value={form.data[activeLocale]?.eyebrow ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.eyebrow`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.eyebrow` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-hero_eyebrow`}>
                            {t('Hero-eyebrow')}
                        </Label>
                        <Input
                            id={`${activeLocale}-hero_eyebrow`}
                            value={form.data[activeLocale]?.hero_eyebrow ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.hero_eyebrow`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.hero_eyebrow` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-hero_subtitle`}>
                            {t('Hero-ondertitel')}
                        </Label>
                        <Textarea
                            id={`${activeLocale}-hero_subtitle`}
                            value={form.data[activeLocale]?.hero_subtitle ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.hero_subtitle`,
                                    event.target.value,
                                )
                            }
                            className="min-h-24 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.hero_subtitle` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-description`}>
                            {t('Productbeschrijving')}
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
                            className="min-h-32 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.description` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label
                            htmlFor={`${activeLocale}-expected_delivery_label`}
                        >
                            {t('Verwachte levering')}
                        </Label>
                        <Input
                            id={`${activeLocale}-expected_delivery_label`}
                            value={
                                form.data[activeLocale]
                                    ?.expected_delivery_label ?? ''
                            }
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.expected_delivery_label`,
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.expected_delivery_label` as keyof typeof form.errors
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
