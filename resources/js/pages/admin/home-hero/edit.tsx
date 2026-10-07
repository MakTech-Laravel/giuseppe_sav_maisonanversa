import { Head, useForm } from '@inertiajs/react';
import { ImageIcon, Loader2, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { HomeHeroTranslationsDialog } from '@/components/admin/home-hero-translations-dialog';
import FileUpload from '@/components/file-upload';
import type { ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import homeHeroRoutes from '@/routes/admin/home-hero';

type Slot = 'primary' | 'secondary' | 'tertiary';

type Option = {
    value: string;
    label: string;
};

type HeroForm = {
    eyebrow: string;
    title: string;
    title_accent: string;
    tagline: string;
    show_counter: boolean;
    counter_line_one: string;
    counter_line_two: string;
    primary_label: string;
    primary_action: string;
    primary_target: string;
    secondary_label: string;
    secondary_action: string;
    secondary_target: string;
    tertiary_label: string;
    tertiary_action: string;
    tertiary_target: string;
    image: File | null;
    remove_image: boolean;
};

type HeroRecord = Omit<HeroForm, 'image' | 'remove_image'> & {
    image_url: string | null;
};

const SLOTS: Array<{ key: Slot; title: string }> = [
    { key: 'primary', title: 'Primaire knop' },
    { key: 'secondary', title: 'Secundaire knop' },
    { key: 'tertiary', title: 'Tertiaire knop' },
];

export default function HomeHeroEdit({
    hero,
    actions,
    pages,
    fallbackImageUrl,
    locales,
    translations,
    translationStatus,
}: {
    hero: HeroRecord;
    actions: Option[];
    pages: Option[];
    fallbackImageUrl: string | null;
    locales: string[];
    translations: Record<string, Record<string, string>>;
    translationStatus: Record<string, Record<string, boolean>>;
}) {
    const { t } = useTranslation();
    const submitTo = homeHeroRoutes.update.form(wayfinderLocale());
    const form = useForm({ url: submitTo.action, method: submitTo.method }, {
        eyebrow: hero.eyebrow,
        title: hero.title,
        title_accent: hero.title_accent,
        tagline: hero.tagline,
        show_counter: hero.show_counter,
        counter_line_one: hero.counter_line_one,
        counter_line_two: hero.counter_line_two,
        primary_label: hero.primary_label,
        primary_action: hero.primary_action,
        primary_target: hero.primary_target,
        secondary_label: hero.secondary_label,
        secondary_action: hero.secondary_action,
        secondary_target: hero.secondary_target,
        tertiary_label: hero.tertiary_label,
        tertiary_action: hero.tertiary_action,
        tertiary_target: hero.tertiary_target,
        image: null,
        remove_image: false,
    } satisfies HeroForm);

    const [previewFileUrl, setPreviewFileUrl] = useState<string | null>(null);
    const previewImage =
        previewFileUrl ??
        (form.data.remove_image ? null : hero.image_url) ??
        fallbackImageUrl;

    const existingFiles: ExistingFile[] =
        hero.image_url && !form.data.image && !form.data.remove_image
            ? [
                  {
                      id: 'current',
                      path: hero.image_url,
                      url: hero.image_url,
                      mime_type: 'image/*',
                  },
              ]
            : [];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    const setSlot = (slot: Slot, field: 'label' | 'action' | 'target', value: string) => {
        form.setData(`${slot}_${field}`, value);
    };

    return (
        <>
            <Head title={t('Home hero')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Home hero')}
                    description={t(
                        'Beeld, tekst en knoppen van de openingsfoto op de homepage.',
                    )}
                    icon={ImageIcon}
                />
                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        className="xl:grid-cols-[minmax(0,1fr)_24rem]"
                        aside={
                            <>
                                <AdminPanel title={t('Voorbeeld')}>
                                    <HeroPreview
                                        data={form.data}
                                        imageUrl={previewImage}
                                        actions={actions}
                                    />
                                    <p className="mt-3 text-xs text-muted-foreground">
                                        {t(
                                            'Het getal op de site komt uit de inventaris.',
                                        )}
                                    </p>
                                </AdminPanel>
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
                                            {t('Home hero opslaan')}
                                        </Button>
                                        <HomeHeroTranslationsDialog
                                            locales={locales}
                                            translations={translations}
                                            translationStatus={translationStatus}
                                        />
                                    </div>
                                </AdminPanel>
                            </>
                        }
                    >
                        <AdminPanel
                            title={t('Fotografie')}
                            description={t(
                                'Wanneer er geen foto is geüpload, blijft de huidige mansion-foto staan.',
                            )}
                        >
                            <FileUpload
                                accept="image/png,image/jpeg,image/webp"
                                maxSize={false}
                                value={form.data.image}
                                onChange={(file) => {
                                    const next = (file as File | null) ?? null;
                                    form.setData('image', next);
                                    form.setData('remove_image', false);
                                    setPreviewFileUrl((current) => {
                                        if (current) {
                                            URL.revokeObjectURL(current);
                                        }

                                        return next
                                            ? URL.createObjectURL(next)
                                            : null;
                                    });
                                }}
                                existingFiles={existingFiles}
                                onRemoveExisting={() =>
                                    form.setData('remove_image', true)
                                }
                                placeholder={t(
                                    'Sleep een afbeelding hierheen of klik om te bladeren',
                                )}
                                hint={t('PNG, JPG of WEBP')}
                                error={form.errors.image}
                            />
                        </AdminPanel>

                        <AdminPanel
                            title={t('Tekst')}
                            description={t(
                                'Bewerk de Nederlandse bron. DeepL vult Engels en Frans; corrigeer die in Vertalingen.',
                            )}
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    id="eyebrow"
                                    label={t('Wenkbrauw')}
                                    value={form.data.eyebrow}
                                    error={form.errors.eyebrow}
                                    className="sm:col-span-2"
                                    onChange={(value) =>
                                        form.setData('eyebrow', value)
                                    }
                                />
                                <Field
                                    id="title"
                                    label={t('Titel')}
                                    value={form.data.title}
                                    error={form.errors.title}
                                    onChange={(value) =>
                                        form.setData('title', value)
                                    }
                                />
                                <Field
                                    id="title_accent"
                                    label={t('Titelregel 2')}
                                    value={form.data.title_accent}
                                    error={form.errors.title_accent}
                                    onChange={(value) =>
                                        form.setData('title_accent', value)
                                    }
                                />
                                <Field
                                    id="tagline"
                                    label={t('Ondertitel')}
                                    value={form.data.tagline}
                                    error={form.errors.tagline}
                                    className="sm:col-span-2"
                                    onChange={(value) =>
                                        form.setData('tagline', value)
                                    }
                                />
                            </div>
                        </AdminPanel>

                        <AdminPanel
                            title={t('Knoppen')}
                            description={t(
                                'Elke knop heeft een label en een vaste bestemming. Verborgen of lege knoppen verschijnen niet.',
                            )}
                        >
                            <div className="space-y-6">
                                {SLOTS.map((slot) => (
                                    <ButtonSlotFields
                                        key={slot.key}
                                        slot={slot.key}
                                        title={t(slot.title)}
                                        data={form.data}
                                        errors={form.errors}
                                        actions={actions}
                                        pages={pages}
                                        onChange={setSlot}
                                    />
                                ))}
                            </div>
                        </AdminPanel>

                        <AdminPanel
                            title={t('Teller')}
                            description={t(
                                'Het getal komt uit de inventaris. Alleen het bijschrift is bewerkbaar.',
                            )}
                        >
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="show_counter"
                                        checked={form.data.show_counter}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'show_counter',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label htmlFor="show_counter">
                                        {t('Toon de live voorraad')}
                                    </Label>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        id="counter_line_one"
                                        label={t('Regel 1')}
                                        value={form.data.counter_line_one}
                                        error={form.errors.counter_line_one}
                                        onChange={(value) =>
                                            form.setData(
                                                'counter_line_one',
                                                value,
                                            )
                                        }
                                    />
                                    <Field
                                        id="counter_line_two"
                                        label={t('Regel 2')}
                                        value={form.data.counter_line_two}
                                        error={form.errors.counter_line_two}
                                        onChange={(value) =>
                                            form.setData(
                                                'counter_line_two',
                                                value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        </AdminPanel>
                    </AdminResourceShell>
                </form>
            </div>
        </>
    );
}

function Field({
    id,
    label,
    value,
    error,
    className,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    className?: string;
    onChange: (value: string) => void;
}) {
    return (
        <div className={className ? `space-y-2 ${className}` : 'space-y-2'}>
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
            <InputError message={error} />
        </div>
    );
}

function ButtonSlotFields({
    slot,
    title,
    data,
    errors,
    actions,
    pages,
    onChange,
}: {
    slot: Slot;
    title: string;
    data: HeroForm;
    errors: Partial<Record<keyof HeroForm, string>>;
    actions: Option[];
    pages: Option[];
    onChange: (slot: Slot, field: 'label' | 'action' | 'target', value: string) => void;
}) {
    const { t } = useTranslation();
    const action = data[`${slot}_action`];
    const label = data[`${slot}_label`];
    const target = data[`${slot}_target`];

    return (
        <fieldset className="space-y-4 rounded-lg border bg-muted/20 p-4">
            <legend className="px-1 text-sm font-medium">{title}</legend>
            <div className="grid gap-4 lg:grid-cols-2">
                <Field
                    id={`${slot}_label`}
                    label={t('Knoptekst')}
                    value={label}
                    error={errors[`${slot}_label`]}
                    onChange={(value) => onChange(slot, 'label', value)}
                />
                <div className="space-y-2">
                    <Label htmlFor={`${slot}_action`}>{t('Bestemming')}</Label>
                    <Select
                        value={action}
                        onValueChange={(value) => onChange(slot, 'action', value)}
                    >
                        <SelectTrigger id={`${slot}_action`} className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {actions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors[`${slot}_action`]} />
                </div>
                {action === 'maison_page' ? (
                    <div className="space-y-2 lg:col-span-2">
                        <Label htmlFor={`${slot}_target`}>{t('Pagina')}</Label>
                        <Select
                            value={target || undefined}
                            onValueChange={(value) =>
                                onChange(slot, 'target', value)
                            }
                        >
                            <SelectTrigger id={`${slot}_target`} className="w-full">
                                <SelectValue placeholder={t('Kies een Maison-pagina.')} />
                            </SelectTrigger>
                            <SelectContent>
                                {pages.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {t(option.label)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors[`${slot}_target`]} />
                    </div>
                ) : null}
                {action === 'external' ? (
                    <Field
                        id={`${slot}_target`}
                        label={t('HTTPS-adres')}
                        value={target}
                        error={errors[`${slot}_target`]}
                        className="lg:col-span-2"
                        onChange={(value) => onChange(slot, 'target', value)}
                    />
                ) : null}
            </div>
        </fieldset>
    );
}

function HeroPreview({
    data,
    imageUrl,
    actions,
}: {
    data: HeroForm;
    imageUrl: string | null;
    actions: Option[];
}) {
    const { t } = useTranslation();
    const visible = SLOTS.filter((slot) => {
        const action = data[`${slot.key}_action`];
        const label = data[`${slot.key}_label`];

        return action !== 'hidden' && label.trim() !== '';
    });

    return (
        <div className="relative aspect-[4/5] overflow-hidden rounded-lg bg-[#291c18] text-[#f4efe6]">
            {imageUrl ? (
                <img
                    src={imageUrl}
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover"
                />
            ) : null}
            <div className="absolute inset-0 bg-linear-to-b from-[#291c18]/25 via-transparent to-[#291c18]/85" />
            <div className="relative flex h-full flex-col justify-end p-4">
                <p className="text-[8px] tracking-[0.28em] text-[#c4a574] uppercase">
                    {data.eyebrow}
                </p>
                <p className="mt-2 font-serif text-3xl leading-none tracking-[0.12em] uppercase">
                    {data.title}
                </p>
                <p className="mt-1 font-serif text-lg tracking-[0.16em] text-[#f4efe6]/60 uppercase">
                    {data.title_accent}
                </p>
                <div className="my-3 h-px w-8 bg-[#c4a574]" />
                <p className="text-[8px] leading-relaxed tracking-[0.18em] text-[#c4a574] uppercase">
                    {data.tagline}
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    {visible.map((slot, index) => {
                        const action = actions.find(
                            (option) => option.value === data[`${slot.key}_action`],
                        );

                        return (
                            <span
                                key={slot.key}
                                className={
                                    index === 0
                                        ? 'border border-[#f4efe6]/40 px-2 py-1 text-[8px] tracking-[0.16em] uppercase'
                                        : 'border-b border-[#f4efe6]/30 pb-0.5 text-[8px] tracking-[0.16em] text-[#f4efe6]/70 uppercase'
                                }
                            >
                                {data[`${slot.key}_label`]}
                                <span className="sr-only">
                                    {action ? t(action.label) : ''}
                                </span>
                            </span>
                        );
                    })}
                </div>
                {data.show_counter ? (
                    <div className="absolute top-4 right-4 text-right">
                        <div className="font-serif text-4xl leading-none text-[#c4a574]">
                            00
                        </div>
                        <div className="mt-1 text-[7px] tracking-[0.22em] text-[#f4efe6]/70 uppercase">
                            {data.counter_line_one}
                            <br />
                            {data.counter_line_two}
                        </div>
                    </div>
                ) : null}
            </div>
        </div>
    );
}
