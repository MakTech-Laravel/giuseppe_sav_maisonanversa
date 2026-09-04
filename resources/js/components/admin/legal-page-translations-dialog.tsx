import { router, useForm } from '@inertiajs/react';
import { Languages, Loader2, RefreshCw, TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LegalRichTextEditor } from '@/components/admin/legal-rich-text-editor';
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
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import legalPages from '@/routes/admin/legal-pages';

type LocaleCopy = {
    body: string;
};

type TranslationStatus = {
    body: boolean;
};

type LegalLocale = 'nl' | 'en' | 'fr';

interface LegalPageTranslationsDialogProps {
    pageId: string;
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
        nl: { body: translations.nl?.body ?? '' },
        en: { body: translations.en?.body ?? '' },
        fr: { body: translations.fr?.body ?? '' },
    };
}

export function LegalPageTranslationsDialog({
    pageId,
    locales,
    translations,
    translationStatus,
}: LegalPageTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<LegalLocale>('nl');

    const form = useForm(
        legalPages.translations.update({
            locale,
            legalPage: Number(pageId),
        }),
        initialFormData(translations),
    );

    function handleOpenChange(nextOpen: boolean) {
        if (nextOpen) {
            setActiveLocale((locales[0] as LegalLocale | undefined) ?? 'nl');
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

    function retranslate(targetLocale?: LegalLocale) {
        setTranslating(true);
        router.post(
            legalPages.translate({
                locale,
                legalPage: Number(pageId),
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

        return !status?.body;
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
                            'Bewerk de Nederlandse bron en de EN/FR-vertalingen. DeepL vult ontbrekende talen; HTML-tags blijven ongewijzigd.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations ? (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla de pagina opnieuw op of gebruik DeepL om ze te genereren.',
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
                            onClick={() => setActiveLocale(code as LegalLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>
                            {t('Inhoud')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <LegalRichTextEditor
                            key={activeLocale}
                            value={form.data[activeLocale]?.body ?? ''}
                            onChange={(body) =>
                                form.setData(`${activeLocale}.body`, body)
                            }
                            error={
                                form.errors[
                                    `${activeLocale}.body` as keyof typeof form.errors
                                ]
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.body` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>

                    <DialogFooter className="flex-col gap-2 sm:flex-row sm:justify-between">
                        <div className="flex flex-wrap gap-2">
                            {activeLocale !== 'nl' ? (
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
                            ) : null}
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
