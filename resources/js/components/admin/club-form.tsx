import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
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
import { Textarea } from '@/components/ui/textarea';
import type { AdminClubFormValues, ClubFormOptions } from '@/types/club';

type ClubFormProps = {
    options: ClubFormOptions;
    club?: AdminClubFormValues;
    /** POST for create, PUT-via-POST for edit; both carry an image upload. */
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
};

export function ClubForm({
    options,
    club,
    action,
    method,
    submitLabel,
}: ClubFormProps) {
    const { t } = useTranslation();
    const [preview, setPreview] = useState<string | null>(
        club?.image_url ?? null,
    );

    const form = useForm({
        _method: method === 'put' ? 'put' : undefined,
        name: club?.name ?? '',
        sports: club?.sports ?? ['padel'],
        street: club?.street ?? '',
        postal_code: club?.postal_code ?? '',
        city: club?.city ?? '',
        country: club?.country ?? 'BE',
        lat: club?.lat != null ? String(club.lat) : '',
        lng: club?.lng != null ? String(club.lng) : '',
        website: club?.website ?? '',
        phone: club?.phone ?? '',
        status: club?.status ?? 'approved',
        is_partner: club?.is_partner ?? false,
        is_session_venue: club?.is_session_venue ?? true,
        show_on_corner_page: club?.show_on_corner_page ?? false,
        corner_pipeline_status: club?.corner_pipeline_status ?? '',
        has_corner: club?.has_corner ?? false,
        corner_published: club?.corner_published ?? false,
        corner_title: club?.corner_title ?? '',
        corner_body: club?.corner_body ?? '',
        corner_location: club?.corner_location ?? '',
        sort_order: club?.sort_order ?? 0,
        image: null as File | null,
        remove_image: false,
    });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post(action, { forceFormData: true });
    }

    function toggleSport(sport: string) {
        form.setData(
            'sports',
            form.data.sports.includes(sport)
                ? form.data.sports.filter((item) => item !== sport)
                : [...form.data.sports, sport],
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-8">
            <section className="space-y-6">
                <h2 className="font-sans text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    {t('Locatie')}
                </h2>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={t('Naam')} error={form.errors.name} required>
                        <Input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                        />
                    </Field>

                    <Field label={t('Status')} error={form.errors.status}>
                        <Select
                            value={form.data.status}
                            onValueChange={(value) =>
                                form.setData('status', value)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.statuses.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {t(option.label)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                </div>

                <Field label={t('Sporten')} error={form.errors.sports}>
                    <div className="flex gap-4">
                        {options.sports.map((sport) => (
                            <label
                                key={sport.value}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={form.data.sports.includes(
                                        sport.value,
                                    )}
                                    onCheckedChange={() =>
                                        toggleSport(sport.value)
                                    }
                                />
                                {t(sport.label)}
                            </label>
                        ))}
                    </div>
                </Field>

                <div className="grid gap-4 sm:grid-cols-[1fr_10rem_1fr_6rem]">
                    <Field
                        label={t('Straat en nummer')}
                        error={form.errors.street}
                    >
                        <Input
                            value={form.data.street}
                            onChange={(event) =>
                                form.setData('street', event.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label={t('Postcode')}
                        error={form.errors.postal_code}
                    >
                        <Input
                            value={form.data.postal_code}
                            onChange={(event) =>
                                form.setData('postal_code', event.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('Stad')} error={form.errors.city} required>
                        <Input
                            value={form.data.city}
                            onChange={(event) =>
                                form.setData('city', event.target.value)
                            }
                            required
                        />
                    </Field>
                    <Field label={t('Land')} error={form.errors.country}>
                        <Input
                            value={form.data.country}
                            maxLength={2}
                            onChange={(event) =>
                                form.setData(
                                    'country',
                                    event.target.value.toUpperCase(),
                                )
                            }
                        />
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-4">
                    <Field label={t('Website')} error={form.errors.website}>
                        <Input
                            type="url"
                            value={form.data.website}
                            placeholder="https://"
                            onChange={(event) =>
                                form.setData('website', event.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('Telefoon')} error={form.errors.phone}>
                        <Input
                            value={form.data.phone}
                            onChange={(event) =>
                                form.setData('phone', event.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('Breedtegraad')} error={form.errors.lat}>
                        <Input
                            value={form.data.lat}
                            onChange={(event) =>
                                form.setData('lat', event.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('Lengtegraad')} error={form.errors.lng}>
                        <Input
                            value={form.data.lng}
                            onChange={(event) =>
                                form.setData('lng', event.target.value)
                            }
                        />
                    </Field>
                </div>

                <Field label={t('Afbeelding')} error={form.errors.image}>
                    <div className="flex items-center gap-4">
                        {preview && (
                            <img
                                src={preview}
                                alt=""
                                className="size-16 rounded object-cover"
                            />
                        )}
                        <Input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => {
                                const file = event.target.files?.[0] ?? null;
                                form.setData('image', file);
                                form.setData('remove_image', false);
                                setPreview(
                                    file
                                        ? URL.createObjectURL(file)
                                        : (club?.image_url ?? null),
                                );
                            }}
                            className="max-w-sm"
                        />
                        {club?.image_url && (
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.remove_image}
                                    onCheckedChange={(checked) => {
                                        form.setData(
                                            'remove_image',
                                            checked === true,
                                        );

                                        if (checked === true) {
                                            form.setData('image', null);
                                            setPreview(null);
                                        }
                                    }}
                                />
                                {t('Afbeelding verwijderen')}
                            </label>
                        )}
                    </div>
                </Field>

                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.is_session_venue}
                        onCheckedChange={(checked) =>
                            form.setData('is_session_venue', checked === true)
                        }
                    />
                    {t('Zichtbaar in sessiezoekopdracht')}
                </label>
            </section>

            <section className="space-y-4 border-t pt-6">
                <h2 className="font-sans text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    {t('Partnerclub')}
                </h2>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.is_partner}
                        onCheckedChange={(checked) =>
                            form.setData('is_partner', checked === true)
                        }
                    />
                    {t('Partnerclub')}
                </label>
            </section>

            <section className="space-y-4 border-t pt-6">
                <h2 className="font-sans text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    {t('Club Corner pagina')}
                </h2>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.show_on_corner_page}
                        onCheckedChange={(checked) =>
                            form.setData(
                                'show_on_corner_page',
                                checked === true,
                            )
                        }
                    />
                    {t('Tonen op Club Corner pagina')}
                </label>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        label={t('Pipeline status')}
                        error={form.errors.corner_pipeline_status}
                    >
                        <Select
                            value={form.data.corner_pipeline_status || '__none'}
                            onValueChange={(value) =>
                                form.setData(
                                    'corner_pipeline_status',
                                    value === '__none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder={t('Geen')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__none">
                                    {t('Geen')}
                                </SelectItem>
                                {options.pipelineStatuses.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {t(option.label)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field
                        label={t('Volgorde')}
                        error={form.errors.sort_order}
                    >
                        <Input
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData(
                                    'sort_order',
                                    Number(event.target.value) || 0,
                                )
                            }
                        />
                    </Field>
                </div>
            </section>

            <section className="space-y-4 border-t pt-6">
                <h2 className="font-sans text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    {t('Club Corner kaart')}
                </h2>
                <div className="flex flex-wrap gap-4">
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.has_corner}
                            onCheckedChange={(checked) =>
                                form.setData('has_corner', checked === true)
                            }
                        />
                        {t('Heeft Club Corner')}
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.corner_published}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'corner_published',
                                    checked === true,
                                )
                            }
                        />
                        {t('Club Corner gepubliceerd')}
                    </label>
                </div>
                <Field
                    label={t('Corner titel')}
                    error={form.errors.corner_title}
                >
                    <Input
                        value={form.data.corner_title}
                        onChange={(event) =>
                            form.setData('corner_title', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label={t('Corner tekst')}
                    error={form.errors.corner_body}
                >
                    <Textarea
                        value={form.data.corner_body}
                        rows={4}
                        onChange={(event) =>
                            form.setData('corner_body', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label={t('Corner locatie')}
                    error={form.errors.corner_location}
                >
                    <Input
                        value={form.data.corner_location}
                        onChange={(event) =>
                            form.setData('corner_location', event.target.value)
                        }
                    />
                </Field>
            </section>

            <Button type="submit" disabled={form.processing}>
                {submitLabel}
            </Button>
        </form>
    );
}

function Field({
    label,
    error,
    required = false,
    children,
}: {
    label: string;
    error?: string;
    required?: boolean;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label>
                {label}
                {required && <span className="text-destructive"> *</span>}
            </Label>
            {children}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
