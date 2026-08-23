import FileUpload from '@/components/file-upload';
import type { ExistingFile } from '@/components/file-upload';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

export const EVENT_CAPACITY_PRESETS = [
    { value: '', labelKey: 'Onbeperkt' },
    { value: '8', labelKey: '8' },
    { value: '10', labelKey: '10' },
    { value: '12', labelKey: '12' },
    { value: '16', labelKey: '16' },
    { value: '20', labelKey: '20' },
    { value: '24', labelKey: '24' },
    { value: '30', labelKey: '30' },
    { value: '40', labelKey: '40' },
    { value: '50', labelKey: '50' },
] as const;

export type CommunityEventFormData = {
    title: string;
    description: string;
    starts_at: string;
    location: string;
    capacity: string;
    thumbnail: File | null;
    remove_thumbnail: boolean;
};

type CommunityEventFormFieldsProps = {
    data: CommunityEventFormData;
    errors: Partial<Record<keyof CommunityEventFormData, string>>;
    setData: <K extends keyof CommunityEventFormData>(
        key: K,
        value: CommunityEventFormData[K],
    ) => void;
    existingThumbnailUrl?: string | null;
};

export function CommunityEventFormFields({
    data,
    errors,
    setData,
    existingThumbnailUrl = null,
}: CommunityEventFormFieldsProps) {
    const { t } = useTranslation();

    const existingFiles: ExistingFile[] =
        existingThumbnailUrl && !data.thumbnail && !data.remove_thumbnail
            ? [
                  {
                      id: 'current',
                      path: existingThumbnailUrl,
                      url: existingThumbnailUrl,
                      mime_type: 'image/*',
                  },
              ]
            : [];

    return (
        <>
            <div className="space-y-2">
                <Label htmlFor="title">{t('Titel')}</Label>
                <Input
                    id="title"
                    value={data.title}
                    onChange={(event) => setData('title', event.target.value)}
                />
                {errors.title && (
                    <p className="text-sm text-destructive">{errors.title}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">{t('Beschrijving')}</Label>
                <textarea
                    id="description"
                    value={data.description}
                    onChange={(event) =>
                        setData('description', event.target.value)
                    }
                    className="min-h-28 w-full rounded-md border px-3 py-2 text-sm"
                />
                {errors.description && (
                    <p className="text-sm text-destructive">
                        {errors.description}
                    </p>
                )}
            </div>

            <div className="grid gap-5 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="starts_at">{t('Datum')}</Label>
                    <Input
                        id="starts_at"
                        type="datetime-local"
                        value={data.starts_at}
                        onChange={(event) =>
                            setData('starts_at', event.target.value)
                        }
                    />
                    {errors.starts_at && (
                        <p className="text-sm text-destructive">
                            {errors.starts_at}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="location">{t('Locatie')}</Label>
                    <Input
                        id="location"
                        value={data.location}
                        onChange={(event) =>
                            setData('location', event.target.value)
                        }
                    />
                    {errors.location && (
                        <p className="text-sm text-destructive">
                            {errors.location}
                        </p>
                    )}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="capacity">{t('Capaciteit')}</Label>
                <Input
                    id="capacity"
                    type="number"
                    min={1}
                    max={500}
                    placeholder={t('Laat leeg voor onbeperkt')}
                    value={data.capacity}
                    onChange={(event) =>
                        setData('capacity', event.target.value)
                    }
                />
                <div className="flex flex-wrap gap-2">
                    {EVENT_CAPACITY_PRESETS.map((preset) => {
                        const active = data.capacity === preset.value;

                        return (
                            <Button
                                key={preset.labelKey}
                                type="button"
                                size="sm"
                                variant={active ? 'default' : 'outline'}
                                className={cn('h-8 px-3 text-xs')}
                                onClick={() =>
                                    setData('capacity', preset.value)
                                }
                            >
                                {t(preset.labelKey)}
                            </Button>
                        );
                    })}
                </div>
                {errors.capacity && (
                    <p className="text-sm text-destructive">
                        {errors.capacity}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label>{t('Thumbnail')}</Label>
                <FileUpload
                    accept="image/png,image/jpeg,image/webp"
                    maxSize={false}
                    value={data.thumbnail}
                    onChange={(file) => {
                        setData('thumbnail', (file as File | null) ?? null);
                        setData('remove_thumbnail', false);
                    }}
                    existingFiles={existingFiles}
                    onRemoveExisting={() => setData('remove_thumbnail', true)}
                    placeholder={t(
                        'Sleep een thumbnail hierheen of klik om te bladeren',
                    )}
                    hint={t('PNG, JPG of WEBP')}
                    error={errors.thumbnail}
                />
            </div>
        </>
    );
}

export function capacityToFormValue(capacity: number | null | undefined): string {
    if (capacity === null || capacity === undefined) {
        return '';
    }

    return String(capacity);
}

export function capacityFromFormValue(value: string): number | null {
    if (value === '' || value === 'none') {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}
