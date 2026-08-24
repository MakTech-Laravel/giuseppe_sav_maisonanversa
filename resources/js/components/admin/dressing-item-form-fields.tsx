import { useTranslation } from 'react-i18next';
import FileUpload from '@/components/file-upload';
import type { ExistingFile } from '@/components/file-upload';
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

export type DressingItemFormData = {
    name: string;
    slug: string;
    category: string;
    description: string;
    status: 'coming_soon' | 'available';
    sort_order: string;
    is_published: boolean;
    image_key: string;
    image: File | null;
    remove_image: boolean;
};

type DressingItemFormFieldsProps = {
    data: DressingItemFormData;
    errors: Partial<Record<keyof DressingItemFormData, string>>;
    setData: <K extends keyof DressingItemFormData>(
        key: K,
        value: DressingItemFormData[K],
    ) => void;
    existingImageUrl?: string | null;
};

export function DressingItemFormFields({
    data,
    errors,
    setData,
    existingImageUrl = null,
}: DressingItemFormFieldsProps) {
    const { t } = useTranslation();

    const existingFiles: ExistingFile[] =
        existingImageUrl && !data.image && !data.remove_image
            ? [
                  {
                      id: 'current',
                      path: existingImageUrl,
                      url: existingImageUrl,
                      mime_type: 'image/*',
                  },
              ]
            : [];

    return (
        <>
            <div className="grid gap-5 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="name">{t('Naam')}</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                    />
                    {errors.name && (
                        <p className="text-sm text-destructive">
                            {errors.name}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="slug">{t('Slug')}</Label>
                    <Input
                        id="slug"
                        value={data.slug}
                        placeholder={t('Automatisch op basis van naam')}
                        onChange={(event) =>
                            setData('slug', event.target.value)
                        }
                    />
                    {errors.slug && (
                        <p className="text-sm text-destructive">
                            {errors.slug}
                        </p>
                    )}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="category">{t('Categorie')}</Label>
                <Input
                    id="category"
                    value={data.category}
                    onChange={(event) =>
                        setData('category', event.target.value)
                    }
                />
                {errors.category && (
                    <p className="text-sm text-destructive">
                        {errors.category}
                    </p>
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
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select
                        value={data.status}
                        onValueChange={(value) =>
                            setData(
                                'status',
                                value as DressingItemFormData['status'],
                            )
                        }
                    >
                        <SelectTrigger id="status" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="coming_soon">
                                {t('Binnenkort')}
                            </SelectItem>
                            <SelectItem value="available">
                                {t('Beschikbaar')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.status && (
                        <p className="text-sm text-destructive">
                            {errors.status}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="sort_order">{t('Volgorde')}</Label>
                    <Input
                        id="sort_order"
                        type="number"
                        min={0}
                        value={data.sort_order}
                        onChange={(event) =>
                            setData('sort_order', event.target.value)
                        }
                    />
                    {errors.sort_order && (
                        <p className="text-sm text-destructive">
                            {errors.sort_order}
                        </p>
                    )}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="image_key">
                    {t('Imagery asset-sleutel (optioneel)')}
                </Label>
                <Input
                    id="image_key"
                    value={data.image_key}
                    placeholder="room-dressing"
                    onChange={(event) =>
                        setData('image_key', event.target.value)
                    }
                />
                <p className="text-xs text-muted-foreground">
                    {t(
                        'Fallback wanneer er geen cover-afbeelding is geüpload.',
                    )}
                </p>
                {errors.image_key && (
                    <p className="text-sm text-destructive">
                        {errors.image_key}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label>{t('Coverafbeelding')}</Label>
                <FileUpload
                    accept="image/png,image/jpeg,image/webp"
                    maxSize={false}
                    value={data.image}
                    onChange={(file) => {
                        setData('image', (file as File | null) ?? null);
                        setData('remove_image', false);
                    }}
                    existingFiles={existingFiles}
                    onRemoveExisting={() => setData('remove_image', true)}
                    placeholder={t(
                        'Sleep een afbeelding hierheen of klik om te bladeren',
                    )}
                    hint={t('PNG, JPG of WEBP')}
                    error={errors.image}
                />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_published"
                    checked={data.is_published}
                    onCheckedChange={(checked) =>
                        setData('is_published', checked === true)
                    }
                />
                <Label htmlFor="is_published" className="font-normal">
                    {t('Gepubliceerd')}
                </Label>
            </div>
        </>
    );
}
