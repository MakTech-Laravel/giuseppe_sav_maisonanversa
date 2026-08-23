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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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

type FaqLocale = 'nl' | 'en' | 'fr';

interface FaqTranslationsDialogProps {
    faqId: string;
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

export function FaqTranslationsDialog({
    faqId,
    locales,
    translations,
    translationStatus,
}: FaqTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<FaqLocale>('nl');

    const form = useForm(
        faqs.translations.update({
            locale,
            faq: faqId,
        }),
        initialFormData(translations),
    );

    useEffect(() => {
        if (! open) {
            return;
        }

        setActiveLocale((locales[0] as FaqLocale | undefined) ?? 'nl');
        form.setData(initialFormData(translations));
    }, [open, faqId, translations, locales]);

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

    const hasPendingTranslations = locales.some((code) => {
        const status = translationStatus[code];

        return ! status?.question || ! status?.answer;
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
                            'Bewerk NL, EN en FR handmatig of genereer opnieuw via DeepL. De brontaal wordt automatisch herkend.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
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
                            onClick={() => setActiveLocale(code as FaqLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-question`}>
                            {t('Vraag')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-question`}
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
                        <Label htmlFor={`${activeLocale}-answer`}>
                            {t('Antwoord')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-answer`}
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
            </DialogContent>
        </Dialog>
    );
}
