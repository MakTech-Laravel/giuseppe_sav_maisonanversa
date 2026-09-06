import { router, useForm } from '@inertiajs/react';
import { Languages, Loader2, RefreshCw, TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import products from '@/routes/admin/products';
import type { ProductSectionCatalogueEntry } from '@/types/admin-product';

type SectionItemCopy = {
    title: string;
    body: string;
};

type SectionCopy = {
    key?: string;
    eyebrow: string;
    heading: string;
    subheading: string;
    intro: string;
    items: Record<string, SectionItemCopy>;
};

type LocalePayload = {
    sections: Record<string, SectionCopy>;
};

type ProductLocale = 'nl' | 'en' | 'fr';

type SectionItemStatus = {
    title: boolean;
    body: boolean;
};

type SectionStatus = {
    eyebrow: boolean;
    heading: boolean;
    subheading: boolean;
    intro: boolean;
    items: Record<string, SectionItemStatus>;
};

type LocaleStatusPayload = {
    sections: Record<string, SectionStatus>;
};

interface ProductSectionTranslationsDialogProps {
    productId: number;
    sectionId: number;
    sectionLabel: string;
    usesHeading: boolean;
    locales: string[];
    catalogue: ProductSectionCatalogueEntry[];
    translations: Record<string, LocalePayload>;
    translationStatus: Record<string, LocaleStatusPayload>;
}

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

function emptySection(): SectionCopy {
    return {
        eyebrow: '',
        heading: '',
        subheading: '',
        intro: '',
        items: {},
    };
}

function emptyPayload(): LocalePayload {
    return { sections: {} };
}

function sliceForSection(
    translations: Record<string, LocalePayload>,
    sectionId: string,
): Record<ProductLocale, LocalePayload> {
    const sliceLocale = (code: ProductLocale): LocalePayload => {
        const section =
            translations[code]?.sections?.[sectionId] ?? emptySection();

        return {
            sections: {
                [sectionId]: section,
            },
        };
    };

    return {
        nl: sliceLocale('nl'),
        en: sliceLocale('en'),
        fr: sliceLocale('fr'),
    };
}

export function ProductSectionTranslationsDialog({
    productId,
    sectionId,
    sectionLabel,
    usesHeading,
    locales,
    translations,
    translationStatus,
}: ProductSectionTranslationsDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const sectionKey = String(sectionId);
    const [open, setOpen] = useState(false);
    const [wasOpen, setWasOpen] = useState(false);
    const [translating, setTranslating] = useState(false);
    const [activeLocale, setActiveLocale] = useState<ProductLocale>('nl');

    const form = useForm(
        products.sections.translations.update({
            locale,
            product: productId,
        }),
        sliceForSection(translations, sectionKey),
    );

    // Reset the form to the latest translations each time the dialog opens.
    // Adjusted during render rather than in an effect — see
    // https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes
    if (open !== wasOpen) {
        setWasOpen(open);

        if (open) {
            setActiveLocale((locales[0] as ProductLocale | undefined) ?? 'nl');
            form.setData(sliceForSection(translations, sectionKey));
        }
    }

    const activeSection = useMemo(() => {
        return (
            form.data[activeLocale]?.sections?.[sectionKey] ?? emptySection()
        );
    }, [form.data, activeLocale, sectionKey]);

    function updateSectionField(
        field: keyof Omit<SectionCopy, 'items' | 'key'>,
        value: string,
    ) {
        const localeData = form.data[activeLocale] ?? emptyPayload();
        const section = localeData.sections[sectionKey] ?? emptySection();

        form.setData(activeLocale, {
            sections: {
                ...localeData.sections,
                [sectionKey]: {
                    ...section,
                    [field]: value,
                },
            },
        });
    }

    function updateItemField(
        itemId: string,
        field: keyof SectionItemCopy,
        value: string,
    ) {
        const localeData = form.data[activeLocale] ?? emptyPayload();
        const section = localeData.sections[sectionKey] ?? emptySection();
        const item = section.items?.[itemId] ?? { title: '', body: '' };

        form.setData(activeLocale, {
            sections: {
                ...localeData.sections,
                [sectionKey]: {
                    ...section,
                    items: {
                        ...section.items,
                        [itemId]: {
                            ...item,
                            [field]: value,
                        },
                    },
                },
            },
        });
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
            products.sections.translate({
                locale,
                product: productId,
            }).url,
            targetLocale ? { target_locale: targetLocale } : {},
            {
                preserveScroll: true,
                onFinish: () => setTranslating(false),
            },
        );
    }

    const hasPendingTranslations = locales.some((code) => {
        const section = translationStatus[code]?.sections?.[sectionKey];
        const nlSection = translations.nl?.sections?.[sectionKey];

        if (!section) {
            return true;
        }

        if (
            usesHeading &&
            ((nlSection?.eyebrow && !section.eyebrow) ||
                (nlSection?.heading && !section.heading) ||
                (nlSection?.subheading && !section.subheading) ||
                (nlSection?.intro && !section.intro))
        ) {
            return true;
        }

        return Object.entries(section.items ?? {}).some(([itemId, item]) => {
            const nlItem = nlSection?.items?.[itemId];

            if (nlItem?.title && !item.title) {
                return true;
            }

            if (nlItem?.body && !item.body) {
                return true;
            }

            return false;
        });
    });

    const itemEntries = Object.entries(activeSection.items ?? {});

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Languages className="h-4 w-4" />
                    {t('Vertalingen')}
                </Button>
            </DialogTrigger>
            <DialogContent
                className="admin-kit max-h-[90vh] overflow-y-auto border-border bg-card text-card-foreground shadow-[0_12px_40px_rgba(41,28,24,0.55)] sm:max-w-2xl"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>
                        {t('Sectievertellingen')}: {sectionLabel}
                    </DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Bewerk vertalingen per taal voor productsecties en onderdelen. DeepL vertaalt vanuit de brontekst.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {hasPendingTranslations ? (
                    <Alert className="border-primary/35 bg-muted text-foreground">
                        <TriangleAlert className="h-4 w-4 text-primary" />
                        <AlertDescription className="text-muted-foreground">
                            {t('Sommige sectievertellingen ontbreken nog.')}
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
                    {usesHeading ? (
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>{t('Eyebrow')}</Label>
                                <Input
                                    value={activeSection.eyebrow ?? ''}
                                    onChange={(event) =>
                                        updateSectionField(
                                            'eyebrow',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>{t('Titel')}</Label>
                                <Input
                                    value={activeSection.heading ?? ''}
                                    onChange={(event) =>
                                        updateSectionField(
                                            'heading',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label>{t('Tweede titelregel')}</Label>
                                <Input
                                    value={activeSection.subheading ?? ''}
                                    onChange={(event) =>
                                        updateSectionField(
                                            'subheading',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label>{t('Introductietekst')}</Label>
                                <Textarea
                                    value={activeSection.intro ?? ''}
                                    maxLength={120}
                                    onChange={(event) =>
                                        updateSectionField(
                                            'intro',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-20 resize-y"
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    {t('{{count}}/120 tekens', {
                                        count: (activeSection.intro ?? '')
                                            .length,
                                    })}
                                </p>
                            </div>
                        </div>
                    ) : null}

                    {itemEntries.map(([itemId, item], index) => (
                        <div
                            key={itemId}
                            className="space-y-3 rounded-lg border border-border bg-muted/20 p-4"
                        >
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('Rij {{number}}', {
                                    number: index + 1,
                                })}
                            </p>
                            <div className="space-y-2">
                                <Label>{t('Titel')}</Label>
                                <Input
                                    value={item.title ?? ''}
                                    onChange={(event) =>
                                        updateItemField(
                                            itemId,
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>{t('Tekst')}</Label>
                                <Textarea
                                    value={item.body ?? ''}
                                    onChange={(event) =>
                                        updateItemField(
                                            itemId,
                                            'body',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-20 resize-y"
                                />
                            </div>
                        </div>
                    ))}

                    {!usesHeading && itemEntries.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('Nog geen onderdelen.')}
                        </p>
                    ) : null}

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
