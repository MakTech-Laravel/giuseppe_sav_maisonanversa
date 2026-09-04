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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import clubsRoutes from '@/routes/admin/clubs';
import type {
    ClubCornerLocaleCopy,
    ClubCornerTranslationStatus,
} from '@/types/club';

type ClubLocale = 'nl' | 'en' | 'fr';

interface ClubTranslationsDialogProps {
    clubId: number;
    locales: string[];
    translations: Record<string, ClubCornerLocaleCopy>;
    translationStatus: Record<string, ClubCornerTranslationStatus>;
}

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

function initialFormData(translations: Record<string, ClubCornerLocaleCopy>) {
    return {
        nl: {
            corner_title: translations.nl?.corner_title ?? '',
            corner_body: translations.nl?.corner_body ?? '',
            corner_location: translations.nl?.corner_location ?? '',
        },
        en: {
            corner_title: translations.en?.corner_title ?? '',
            corner_body: translations.en?.corner_body ?? '',
            corner_location: translations.en?.corner_location ?? '',
        },
        fr: {
            corner_title: translations.fr?.corner_title ?? '',
            corner_body: translations.fr?.corner_body ?? '',
            corner_location: translations.fr?.corner_location ?? '',
        },
    };
}

export function ClubTranslationsDialog({
    clubId,
    locales,
    translations,
    translationStatus,
}: ClubTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<ClubLocale>('nl');

    const form = useForm(
        clubsRoutes.translations.update({
            locale,
            club: clubId,
        }),
        initialFormData(translations),
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        setActiveLocale((locales[0] as ClubLocale | undefined) ?? 'nl');
        form.setData(initialFormData(translations));
    }, [open, clubId, translations, locales]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    function retranslate(targetLocale?: ClubLocale) {
        setTranslating(true);
        router.post(
            clubsRoutes.translate({ locale, club: clubId }).url,
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
            status &&
            (!status.corner_title ||
                !status.corner_body ||
                !status.corner_location)
        );
    });

    const copy = form.data[activeLocale];

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    <Languages className="size-4" />
                    {t('Vertalingen')}
                    {hasPendingTranslations && (
                        <TriangleAlert className="size-3.5 text-amber-500" />
                    )}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{t('Corner vertalingen')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Bewerk vertalingen per taal. Bron tekst wijzig je via Club bewerken; DeepL vertaalt vanuit die bron.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations && (
                    <Alert>
                        <TriangleAlert />
                        <AlertDescription>
                            {t(
                                'Sommige vertalingen ontbreken nog. Sla de Club opnieuw op of gebruik DeepL om ze te genereren.',
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
                            onClick={() => setActiveLocale(code as ClubLocale)}
                        >
                            {LOCALE_LABELS[code] ?? code}
                        </Button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label>{t('Corner titel')}</Label>
                        <Input
                            value={copy?.corner_title ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.corner_title` as never,
                                    event.target.value as never,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.corner_title` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label>{t('Corner tekst')}</Label>
                        <Textarea
                            rows={4}
                            value={copy?.corner_body ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.corner_body` as never,
                                    event.target.value as never,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.corner_body` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label>{t('Corner locatie')}</Label>
                        <Input
                            value={copy?.corner_location ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    `${activeLocale}.corner_location` as never,
                                    event.target.value as never,
                                )
                            }
                        />
                        <InputError
                            message={
                                form.errors[
                                    `${activeLocale}.corner_location` as keyof typeof form.errors
                                ]
                            }
                        />
                    </div>

                    <DialogFooter className="gap-2 sm:justify-between">
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={translating}
                                onClick={() => retranslate(activeLocale)}
                            >
                                {translating ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="size-4" />
                                )}
                                {t('DeepL voor deze taal')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={translating}
                                onClick={() => retranslate()}
                            >
                                {translating ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="size-4" />
                                )}
                                {t('DeepL alle talen')}
                            </Button>
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            {t('Opslaan')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
