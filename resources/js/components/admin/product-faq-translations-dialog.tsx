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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import products from '@/routes/admin/products';

type LocaleCopy = {
    question: string;
    answer: string;
};

type TranslationStatus = {
    question: boolean;
    answer: boolean;
};

type ProductLocale = 'nl' | 'en' | 'fr';

interface ProductFaqTranslationsDialogProps {
    productId: number;
    faqId: number;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
    triggerClassName?: string;
}

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

function initialFormData(translations: Record<string, LocaleCopy>) {
    return {
        nl: {
            question: translations.nl?.question ?? '',
            answer: translations.nl?.answer ?? '',
        },
        en: {
            question: translations.en?.question ?? '',
            answer: translations.en?.answer ?? '',
        },
        fr: {
            question: translations.fr?.question ?? '',
            answer: translations.fr?.answer ?? '',
        },
    };
}

export function ProductFaqTranslationsDialog({
    productId,
    faqId,
    locales,
    translations,
    translationStatus,
    triggerClassName,
}: ProductFaqTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [wasOpen, setWasOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<ProductLocale>('nl');

    const form = useForm(
        products.faqs.translations.update({
            locale,
            product: productId,
            faq: faqId,
        }),
        initialFormData(translations),
    );

    // Reset the form to the latest translations each time the dialog opens.
    // Adjusted during render rather than in an effect — see
    // https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes
    if (open !== wasOpen) {
        setWasOpen(open);

        if (open) {
            setActiveLocale((locales[0] as ProductLocale | undefined) ?? 'nl');
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

    function retranslate(targetLocale?: ProductLocale) {
        setTranslating(true);
        router.post(
            products.faqs.translate({
                locale,
                product: productId,
                faq: faqId,
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

        return !status?.question || !status?.answer;
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className={triggerClassName}
                >
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent
                className="admin-kit max-h-[90vh] overflow-y-auto border-border bg-card text-card-foreground shadow-[0_12px_40px_rgba(41,28,24,0.55)] sm:max-w-2xl"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>{t('FAQ-vertalingen')}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Bewerk vertalingen per taal voor deze product-FAQ. DeepL vertaalt vanuit de brontekst.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations ? (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t('Sommige FAQ-vertalingen ontbreken nog.')}
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
                                setActiveLocale(code as ProductLocale)
                            }
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-product-faq-question`}>
                            {t('Vraag')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-product-faq-question`}
                            value={form.data[activeLocale]?.question ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.question`,
                                    event.target.value,
                                )
                            }
                            className="min-h-24 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.question` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-product-faq-answer`}>
                            {t('Antwoord')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-product-faq-answer`}
                            value={form.data[activeLocale]?.answer ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.answer`,
                                    event.target.value,
                                )
                            }
                            className="min-h-32 resize-y"
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.answer` as keyof typeof form.errors
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
