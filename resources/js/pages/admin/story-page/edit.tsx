import { Head, useForm } from '@inertiajs/react';
import { Loader2, Save, ScrollText } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { STORY_IMAGES, STORY_SECTIONS, STORY_TRANSLATED_FIELDS } from '@/components/admin/story-page-fields';
import { StoryPageTranslationsDialog } from '@/components/admin/story-page-translations-dialog';
import FileUpload from '@/components/file-upload';
import type { ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useLocale } from '@/hooks/use-locale';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import storyPageRoutes from '@/routes/admin/story-page';
import { SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';

type StoryForm = Record<string, string | boolean | File | null>;

function imageFields(): StoryForm {
    const fields: StoryForm = {};

    for (const image of STORY_IMAGES) {
        fields[`${image.slot}_image`] = null;
        fields[`${image.slot}_remove_image`] = false;
    }

    return fields;
}

function copyForLocale(
    page: StoryForm,
    translations: Record<string, Record<string, string>>,
    locale: Locale,
): StoryForm {
    if (locale === SOURCE_LOCALE) {
        return { ...page };
    }

    const bundle = translations[locale] ?? {};
    const next = { ...page };

    for (const field of STORY_TRANSLATED_FIELDS) {
        next[field.key] = bundle[field.key] ?? '';
    }

    return next;
}

export default function StoryPageEdit({
    page,
    locales,
    translations,
    translationStatus,
    fallbacks,
}: {
    page: StoryForm;
    locales: string[];
    translations: Record<string, Record<string, string>>;
    translationStatus: Record<string, Record<string, boolean>>;
    fallbacks: Record<string, string | null>;
}) {
    const { locale } = useLocale();

    return (
        <StoryPageForm
            key={locale}
            locale={locale}
            page={page}
            locales={locales}
            translations={translations}
            translationStatus={translationStatus}
            fallbacks={fallbacks}
        />
    );
}

function StoryPageForm({
    page,
    locales,
    translations,
    translationStatus,
    fallbacks,
    locale,
}: {
    page: StoryForm;
    locales: string[];
    translations: Record<string, Record<string, string>>;
    translationStatus: Record<string, Record<string, boolean>>;
    fallbacks: Record<string, string | null>;
    locale: Locale;
}) {
    const { t } = useTranslation();
    const submitTo = storyPageRoutes.update.form(wayfinderLocale());
    const form = useForm(
        { url: submitTo.action, method: submitTo.method },
        {
            ...copyForLocale(page, translations, locale),
            ...imageFields(),
        },
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    return (
        <>
            <Head title={t('Ons verhaal')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Ons verhaal')}
                    description={t(
                        'Tekst en foto per onderdeel. Een uitgeschakeld onderdeel verdwijnt van de pagina. Zonder nieuwe foto blijft het huidige beeld staan.',
                    )}
                    icon={ScrollText}
                />
                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        className="xl:grid-cols-[minmax(0,1fr)_20rem]"
                        aside={
                            <AdminPanel title={t('Opslaan')}>
                                <div className="space-y-2">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="w-full"
                                    >
                                        {form.processing ? (
                                            <Loader2 className="size-4 animate-spin" />
                                        ) : (
                                            <Save className="size-4" />
                                        )}
                                        {t('Verhaal opslaan')}
                                    </Button>
                                    <StoryPageTranslationsDialog
                                        locales={locales}
                                        translations={translations}
                                        translationStatus={translationStatus}
                                    />
                                </div>
                            </AdminPanel>
                        }
                    >
                        <Accordion type="multiple" defaultValue={['hero']} className="space-y-3">
                            {STORY_SECTIONS.map((section) => (
                                <AccordionItem
                                    key={section.key}
                                    value={section.key}
                                    className="rounded-xl border bg-card px-5 shadow-sm last:border-b"
                                >
                                    <AccordionTrigger className="py-4 text-sm font-semibold tracking-tight hover:no-underline">
                                        {t(section.title)}
                                    </AccordionTrigger>
                                    <AccordionContent>
                                        <div className="space-y-4">
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    id={section.visible}
                                                    checked={form.data[section.visible] === true}
                                                    onCheckedChange={(checked) =>
                                                        form.setData(section.visible, checked === true)
                                                    }
                                                />
                                                <Label htmlFor={section.visible}>{t('Actief')}</Label>
                                            </div>
                                            {STORY_IMAGES.filter((image) => image.section === section.key).map(
                                                (image) => {
                                                    const fileKey = `${image.slot}_image`;
                                                    const removeKey = `${image.slot}_remove_image`;
                                                    const currentUrl = page[`${image.slot}_image_url`];
                                                    const removed = form.data[removeKey] === true;
                                                    const selected = form.data[fileKey];
                                                    const existingFiles: ExistingFile[] =
                                                        typeof currentUrl === 'string' &&
                                                        currentUrl !== '' &&
                                                        !removed &&
                                                        !(selected instanceof File)
                                                            ? [
                                                                  {
                                                                      id: image.slot,
                                                                      path: currentUrl,
                                                                      url: currentUrl,
                                                                      mime_type: 'image/*',
                                                                  },
                                                              ]
                                                            : [];
                                                    const fallback = fallbacks[image.slot];

                                                    return (
                                                        <div key={image.slot} className="space-y-2">
                                                            <Label>{t(image.label)}</Label>
                                                            <FileUpload
                                                                accept="image/png,image/jpeg,image/webp"
                                                                maxSize={false}
                                                                value={selected instanceof File ? selected : null}
                                                                onChange={(file) => {
                                                                    form.setData(fileKey, (file as File | null) ?? null);
                                                                    form.setData(removeKey, false);
                                                                }}
                                                                existingFiles={existingFiles}
                                                                onRemoveExisting={() => form.setData(removeKey, true)}
                                                                placeholder={t(
                                                                    'Sleep een afbeelding hierheen of klik om te bladeren',
                                                                )}
                                                                hint={t('PNG, JPG of WEBP')}
                                                                error={form.errors[fileKey]}
                                                            />
                                                            {!existingFiles.length &&
                                                            !(selected instanceof File) &&
                                                            fallback ? (
                                                                <img
                                                                    src={fallback}
                                                                    alt=""
                                                                    className="h-24 w-auto rounded-md border object-cover"
                                                                />
                                                            ) : null}
                                                            <p className="text-xs text-muted-foreground">
                                                                {t(
                                                                    'Zonder nieuwe foto blijft het huidige beeld staan.',
                                                                )}
                                                            </p>
                                                        </div>
                                                    );
                                                },
                                            )}
                                            {section.fields.map((field) => (
                                                <div key={field.key} className="space-y-2">
                                                    <Label htmlFor={field.key}>
                                                        {t(field.label)}
                                                        {field.translated === false
                                                            ? ` (${t('niet vertaald')})`
                                                            : ''}
                                                    </Label>
                                                    {field.long ? (
                                                        <Textarea
                                                            id={field.key}
                                                            value={String(form.data[field.key] ?? '')}
                                                            onChange={(event) =>
                                                                form.setData(field.key, event.target.value)
                                                            }
                                                            rows={4}
                                                        />
                                                    ) : (
                                                        <Input
                                                            id={field.key}
                                                            value={String(form.data[field.key] ?? '')}
                                                            onChange={(event) =>
                                                                form.setData(field.key, event.target.value)
                                                            }
                                                        />
                                                    )}
                                                    <InputError message={form.errors[field.key]} />
                                                </div>
                                            ))}
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </AdminResourceShell>
                </form>
            </div>
        </>
    );
}
