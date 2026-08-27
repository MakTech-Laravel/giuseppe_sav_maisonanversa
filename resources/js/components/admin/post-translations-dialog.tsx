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
import communityPostsRoutes from '@/routes/admin/community/posts';

type LocaleCopy = {
    content: string;
};

type TranslationStatus = {
    content: boolean;
};

type PostLocale = 'nl' | 'en' | 'fr';

interface PostTranslationsDialogProps {
    postId: string;
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
        nl: { content: translations.nl?.content ?? '' },
        en: { content: translations.en?.content ?? '' },
        fr: { content: translations.fr?.content ?? '' },
    };
}

export function PostTranslationsDialog({
    postId,
    locales,
    translations,
    translationStatus,
}: PostTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<PostLocale>('nl');

    const form = useForm(
        communityPostsRoutes.translations.update({
            locale,
            communityPost: Number(postId),
        }),
        initialFormData(translations),
    );

    useEffect(() => {
        if (! open) {
            return;
        }

        setActiveLocale((locales[0] as PostLocale | undefined) ?? 'nl');
        form.setData(initialFormData(translations));
    }, [open, postId, translations, locales]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function retranslate(targetLocale?: PostLocale) {
        setTranslating(true);
        router.post(
            communityPostsRoutes.translate({
                locale,
                communityPost: Number(postId),
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

        return ! status?.content;
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm" type="button">
                    <Languages className="h-3.5 w-3.5" />
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
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via Bericht bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla het bericht opnieuw op of gebruik DeepL om ze te genereren.',
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
                            onClick={() => setActiveLocale(code as PostLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code.toUpperCase()}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${activeLocale}-content`}>
                            {t('Inhoud')} ({LOCALE_LABELS[activeLocale]})
                        </Label>
                        <Textarea
                            id={`${activeLocale}-content`}
                            value={form.data[activeLocale]?.content ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.content`,
                                    event.target.value,
                                )
                            }
                            className="min-h-32 resize-y"
                            maxLength={2000}
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.content` as keyof typeof form.errors
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
