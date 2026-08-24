import { useTranslation } from 'react-i18next';
import FileUpload from '@/components/file-upload';
import type { ExistingFile } from '@/components/file-upload';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type JournalArticleFormData = {
    slug: string;
    title: string;
    excerpt: string;
    body: string;
    cover_path: string;
    category: string;
    author: string;
    date_label: string;
    published_at: string;
    sort_order: string;
    image: File | null;
    remove_image: boolean;
};

type JournalArticleFormFieldsProps = {
    data: JournalArticleFormData;
    errors: Partial<Record<keyof JournalArticleFormData, string>>;
    setData: <K extends keyof JournalArticleFormData>(
        key: K,
        value: JournalArticleFormData[K],
    ) => void;
    existingImageUrl?: string | null;
};

export function JournalArticleFormFields({
    data,
    errors,
    setData,
    existingImageUrl = null,
}: JournalArticleFormFieldsProps) {
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
                    <Label htmlFor="title">{t('Titel')}</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(event) =>
                            setData('title', event.target.value)
                        }
                    />
                    {errors.title ? (
                        <p className="text-sm text-destructive">
                            {errors.title}
                        </p>
                    ) : null}
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
                    {errors.slug ? (
                        <p className="text-sm text-destructive">
                            {errors.slug}
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="excerpt">{t('Excerpt')}</Label>
                <textarea
                    id="excerpt"
                    value={data.excerpt}
                    onChange={(event) => setData('excerpt', event.target.value)}
                    className="min-h-24 w-full rounded-md border px-3 py-2 text-sm"
                />
                {errors.excerpt ? (
                    <p className="text-sm text-destructive">{errors.excerpt}</p>
                ) : null}
            </div>

            <div className="space-y-2">
                <Label htmlFor="body">{t('Body')}</Label>
                <textarea
                    id="body"
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    className="min-h-56 w-full rounded-md border px-3 py-2 text-sm"
                />
                {errors.body ? (
                    <p className="text-sm text-destructive">{errors.body}</p>
                ) : null}
            </div>

            <div className="grid gap-5 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="category">{t('Categorie')}</Label>
                    <Input
                        id="category"
                        value={data.category}
                        onChange={(event) =>
                            setData('category', event.target.value)
                        }
                    />
                    {errors.category ? (
                        <p className="text-sm text-destructive">
                            {errors.category}
                        </p>
                    ) : null}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="author">{t('Auteur')}</Label>
                    <Input
                        id="author"
                        value={data.author}
                        onChange={(event) =>
                            setData('author', event.target.value)
                        }
                    />
                    {errors.author ? (
                        <p className="text-sm text-destructive">
                            {errors.author}
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="grid gap-5 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="date_label">{t('Datumlabel')}</Label>
                    <Input
                        id="date_label"
                        value={data.date_label}
                        onChange={(event) =>
                            setData('date_label', event.target.value)
                        }
                    />
                    {errors.date_label ? (
                        <p className="text-sm text-destructive">
                            {errors.date_label}
                        </p>
                    ) : null}
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
                    {errors.sort_order ? (
                        <p className="text-sm text-destructive">
                            {errors.sort_order}
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="published_at">{t('Publicatiedatum')}</Label>
                <Input
                    id="published_at"
                    type="datetime-local"
                    value={data.published_at}
                    onChange={(event) =>
                        setData('published_at', event.target.value)
                    }
                />
                {errors.published_at ? (
                    <p className="text-sm text-destructive">
                        {errors.published_at}
                    </p>
                ) : null}
            </div>

            <div className="space-y-2">
                <Label htmlFor="cover_path">
                    {t('Imagery asset-sleutel (optioneel)')}
                </Label>
                <Input
                    id="cover_path"
                    value={data.cover_path}
                    placeholder="antwerp-cityscape"
                    onChange={(event) =>
                        setData('cover_path', event.target.value)
                    }
                />
                <p className="text-xs text-muted-foreground">
                    {t(
                        'Fallback wanneer er geen cover-afbeelding is geüpload.',
                    )}
                </p>
                {errors.cover_path ? (
                    <p className="text-sm text-destructive">
                        {errors.cover_path}
                    </p>
                ) : null}
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
        </>
    );
}
