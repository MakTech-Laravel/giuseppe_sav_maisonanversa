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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import faqs from '@/routes/admin/faqs';

type LocaleCopy = {
    question: string;
    answer: string;
};

type TranslationStatus = {
    question: boolean;
    answer: boolean;
};

interface FaqTranslationsDialogProps {
    faqId: string;
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

export function FaqTranslationsDialog({
    faqId,
    locales,
    defaultLocale,
    translations,
    translationStatus,
}: FaqTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<string>(
        locales.find((code) => code !== defaultLocale) ?? 'en',
    );

    const form = useForm(
        faqs.translations.update({
            locale,
            faq: faqId,
        }),
        {
            en: {
                question: translations.en?.question ?? '',
                answer: translations.en?.answer ?? '',
            },
            fr: {
                question: translations.fr?.question ?? '',
                answer: translations.fr?.answer ?? '',
            },
        },
    );

    useEffect(() => {
        if (! open) {
            return;
        }

        form.setData({
            en: {
                question: translations.en?.question ?? '',
                answer: translations.en?.answer ?? '',
            },
            fr: {
                question: translations.fr?.question ?? '',
                answer: translations.fr?.answer ?? '',
            },
        });
    }, [open, translations.en?.question, translations.en?.answer, translations.fr?.question, translations.fr?.answer]);

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
            faqs.translate({ locale, faq: faqId }).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const pendingEn =
        !translationStatus.en?.question || !translationStatus.en?.answer;
    const pendingFr =
        !translationStatus.fr?.question || !translationStatus.fr?.answer;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="w-full">
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent className="admin-kit max-h-[90vh] overflow-y-auto border-border bg-card text-card-foreground shadow-[0_12px_40px_rgba(41,28,24,0.55)] sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{t('Vertalingen')}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Nederlands is de bron. Engels en Frans kunnen handmatig worden aangepast of opnieuw via DeepL worden gegenereerd.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {(pendingEn || pendingFr) && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla de FAQ opnieuw op of gebruik DeepL om ze te genereren.',
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
                                'Broninhoud (alleen-lezen). Bewerk via FAQ bewerken.',
                            )}
                        </p>
                        <ReadOnlyField
                            label={t('Vraag')}
                            value={translations[defaultLocale]?.question ?? ''}
                        />
                        <ReadOnlyField
                            label={t('Antwoord')}
                            value={translations[defaultLocale]?.answer ?? ''}
                        />
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-question`}>
                                {t('Vraag')} ({LOCALE_LABELS[activeLocale]})
                            </Label>
                            <Textarea
                                id={`${activeLocale}-question`}
                                value={
                                    form.data[
                                        activeLocale as 'en' | 'fr'
                                    ]?.question ?? ''
                                }
                                onChange={(event) =>
                                    form.setData(activeLocale as 'en' | 'fr', {
                                        ...form.data[
                                            activeLocale as 'en' | 'fr'
                                        ],
                                        question: event.target.value,
                                    })
                                }
                                className="min-h-24 resize-y"
                            />
                            {form.errors[
                                `${activeLocale}.question` as keyof typeof form.errors
                            ] ? (
                                <p className="text-sm text-destructive">
                                    {
                                        form.errors[
                                            `${activeLocale}.question` as keyof typeof form.errors
                                        ]
                                    }
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor={`${activeLocale}-answer`}>
                                {t('Antwoord')} ({LOCALE_LABELS[activeLocale]})
                            </Label>
                            <Textarea
                                id={`${activeLocale}-answer`}
                                value={
                                    form.data[
                                        activeLocale as 'en' | 'fr'
                                    ]?.answer ?? ''
                                }
                                onChange={(event) =>
                                    form.setData(activeLocale as 'en' | 'fr', {
                                        ...form.data[
                                            activeLocale as 'en' | 'fr'
                                        ],
                                        answer: event.target.value,
                                    })
                                }
                                className="min-h-32 resize-y"
                            />
                            {form.errors[
                                `${activeLocale}.answer` as keyof typeof form.errors
                            ] ? (
                                <p className="text-sm text-destructive">
                                    {
                                        form.errors[
                                            `${activeLocale}.answer` as keyof typeof form.errors
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
                    'min-h-20 rounded-md border border-border bg-muted px-3 py-2 text-sm text-foreground whitespace-pre-wrap',
                )}
            >
                {value}
            </div>
        </div>
    );
}
