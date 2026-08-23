import { Wand2 } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPanel } from '@/components/admin/admin-resource-shell';
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
import { cn } from '@/lib/utils';

export type ProductFormData = {
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    amount: string;
    edition_total: string;
    edition_number_prefix: string;
    edition_number_postfix: string;
    archive_edition_numbers: number[];
    stock_quantity: string;
    is_published: boolean;
    grants_founding_circle: boolean;
    expected_delivery_label: string;
    primary_image: File | null;
    gallery_images: File[] | null;
    remove_primary_image: boolean;
    gallery_keep: string[];
};

type ProductFormErrors = Partial<
    Record<keyof ProductFormData | 'gallery_images.0' | 'primary_image', string>
>;

function slugify(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function OptionCard({
    selected,
    title,
    description,
    onSelect,
}: {
    selected: boolean;
    title: string;
    description: string;
    onSelect: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onSelect}
            aria-pressed={selected}
            className={cn(
                'rounded-xl border p-4 text-left transition-colors',
                selected
                    ? 'border-primary bg-primary/10 ring-1 ring-primary/40'
                    : 'border-border bg-card hover:border-primary/40 hover:bg-muted/40',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-medium">{title}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {description}
                    </p>
                </div>
                <span
                    className={cn(
                        'mt-0.5 size-4 shrink-0 rounded-full border',
                        selected
                            ? 'border-primary bg-primary'
                            : 'border-muted-foreground/40',
                    )}
                />
            </div>
        </button>
    );
}

export function ProductFormFields({
    data,
    errors,
    setData,
    existingPrimary = null,
    existingGallery = [],
    isUploading = false,
    uploadProgress = null,
    onCancelUpload,
}: {
    data: ProductFormData;
    errors: ProductFormErrors;
    setData: <K extends keyof ProductFormData>(
        key: K,
        value: ProductFormData[K],
    ) => void;
    existingPrimary?: ExistingFile | null;
    existingGallery?: ExistingFile[];
    isUploading?: boolean;
    uploadProgress?: number | null;
    onCancelUpload?: () => void;
}) {
    const { t } = useTranslation();
    const isLimited = data.type === 'limited_edition';
    const editionTotal = Math.min(
        Math.max(0, Number.parseInt(data.edition_total || '0', 10) || 0),
        10000,
    );
    const padWidth = Math.max(3, String(Math.max(editionTotal, 1)).length);

    const formatEditionLabel = (number: number): string =>
        `${data.edition_number_prefix}${String(number).padStart(padWidth, '0')}${data.edition_number_postfix}`;

    const editionNumbers = useMemo(
        () => Array.from({ length: editionTotal }, (_, index) => index + 1),
        [editionTotal],
    );

    const archived = new Set(data.archive_edition_numbers);
    const showExistingPrimary =
        Boolean(existingPrimary) &&
        !data.primary_image &&
        !data.remove_primary_image;

    const toggleArchive = (number: number, checked: boolean) => {
        const next = checked
            ? [...data.archive_edition_numbers, number].sort((a, b) => a - b)
            : data.archive_edition_numbers.filter((value) => value !== number);

        setData('archive_edition_numbers', next);
    };

    const handleEditionTotalChange = (value: string) => {
        setData('edition_total', value);
        const total = Math.min(
            Math.max(0, Number.parseInt(value || '0', 10) || 0),
            10000,
        );
        setData(
            'archive_edition_numbers',
            data.archive_edition_numbers.filter((number) => number <= total),
        );
    };

    return (
        <div className="grid w-full gap-6">
            <AdminPanel
                title={t('Basisgegevens')}
                description={t(
                    'Naam, slug, type en prijs van dit catalogusproduct.',
                )}
            >
                <div className="grid gap-5 md:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('Naam')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            placeholder={t('bijv. Heritage No.002')}
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="slug">{t('Slug')}</Label>
                        <div className="flex gap-2">
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(event) =>
                                    setData('slug', event.target.value)
                                }
                                placeholder={t('bijv. heritage-no-002')}
                                className="flex-1"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                aria-label={t('Genereer slug uit naam')}
                                title={t('Genereer slug uit naam')}
                                disabled={!data.name.trim()}
                                onClick={() =>
                                    setData('slug', slugify(data.name))
                                }
                            >
                                <Wand2 className="h-4 w-4" />
                            </Button>
                        </div>
                        <InputError message={errors.slug} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="type">{t('Type')}</Label>
                        <Select
                            value={data.type}
                            onValueChange={(value) =>
                                setData(
                                    'type',
                                    value as ProductFormData['type'],
                                )
                            }
                        >
                            <SelectTrigger id="type" className="w-full">
                                <SelectValue placeholder={t('Type')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="simple">
                                    {t('Eenvoudige voorraad')}
                                </SelectItem>
                                <SelectItem value="limited_edition">
                                    {t('Gelimiteerde editie')}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.type} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="amount">{t('Bedrag')}</Label>
                        <Input
                            id="amount"
                            type="text"
                            inputMode="decimal"
                            value={data.amount}
                            onChange={(event) =>
                                setData('amount', event.target.value)
                            }
                            placeholder="249.00"
                        />
                        <p className="text-xs text-muted-foreground">EUR</p>
                        <InputError message={errors.amount} />
                    </div>
                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="expected_delivery_label">
                            {t('Verwachte levering')}
                        </Label>
                        <Input
                            id="expected_delivery_label"
                            value={data.expected_delivery_label}
                            onChange={(event) =>
                                setData(
                                    'expected_delivery_label',
                                    event.target.value,
                                )
                            }
                            placeholder={t(
                                'bijv. Q1 2027 — onder voorbehoud van productie',
                            )}
                        />
                        <InputError message={errors.expected_delivery_label} />
                    </div>
                </div>
            </AdminPanel>

            <AdminPanel
                title={isLimited ? t('Editie & voorraad') : t('Voorraad')}
                description={
                    isLimited
                        ? t(
                              'Stel de editiegrootte in en markeer welke nummers in het archief blijven.',
                          )
                        : t('Beheer de eenvoudige voorraad van dit product.')
                }
            >
                {isLimited ? (
                    <div className="grid gap-5">
                        <div className="grid gap-5 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="edition_total">
                                    {t('Editiegrootte')}
                                </Label>
                                <Input
                                    id="edition_total"
                                    type="number"
                                    min={1}
                                    max={10000}
                                    value={data.edition_total}
                                    onChange={(event) =>
                                        handleEditionTotalChange(
                                            event.target.value,
                                        )
                                    }
                                    placeholder="100"
                                />
                                <InputError message={errors.edition_total} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edition_number_prefix">
                                    {t('Prefix')}
                                </Label>
                                <Input
                                    id="edition_number_prefix"
                                    value={data.edition_number_prefix}
                                    onChange={(event) =>
                                        setData(
                                            'edition_number_prefix',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t('bijv. No. of MA-')}
                                />
                                <InputError
                                    message={errors.edition_number_prefix}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edition_number_postfix">
                                    {t('Postfix')}
                                </Label>
                                <Input
                                    id="edition_number_postfix"
                                    value={data.edition_number_postfix}
                                    onChange={(event) =>
                                        setData(
                                            'edition_number_postfix',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t('bijv. -A of /100')}
                                />
                                <InputError
                                    message={errors.edition_number_postfix}
                                />
                            </div>
                        </div>

                        {editionTotal > 0 ? (
                            <p className="text-xs text-muted-foreground">
                                {t('Voorbeeld')}:{' '}
                                <span className="font-mono text-foreground">
                                    {formatEditionLabel(1)}
                                </span>
                                {' · '}
                                <span className="font-mono text-foreground">
                                    {formatEditionLabel(editionTotal)}
                                </span>
                            </p>
                        ) : null}

                        {editionTotal > 0 ? (
                            <div className="grid gap-3">
                                <div className="flex flex-wrap items-end justify-between gap-2">
                                    <div>
                                        <Label>{t('Archiefnummers')}</Label>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {t(
                                                'Selecteer de nummers die niet verkoopbaar zijn. Leeg = alles verkoopbaar.',
                                            )}
                                        </p>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {t('{{count}} gearchiveerd', {
                                            count: data.archive_edition_numbers
                                                .length,
                                        })}
                                    </p>
                                </div>
                                <div className="max-h-96 overflow-y-auto rounded-lg border bg-muted/20 p-3 scrollbar-none">
                                    <div className="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10">
                                        {editionNumbers.map((number) => {
                                            const isArchived =
                                                archived.has(number);
                                            const id = `edition-${number}`;

                                            return (
                                                <label
                                                    key={number}
                                                    htmlFor={id}
                                                    className={cn(
                                                        'flex cursor-pointer flex-col items-center gap-1.5 rounded-md border px-1 py-2 text-center transition-colors',
                                                        isArchived
                                                            ? 'border-gold/40 bg-gold/10'
                                                            : 'border-transparent hover:border-border hover:bg-background',
                                                    )}
                                                >
                                                    <Checkbox
                                                        id={id}
                                                        checked={isArchived}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggleArchive(
                                                                number,
                                                                checked ===
                                                                    true,
                                                            )
                                                        }
                                                    />
                                                    <span className="max-w-full truncate font-mono text-[10px] tabular-nums">
                                                        {formatEditionLabel(
                                                            number,
                                                        )}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                                <InputError
                                    message={errors.archive_edition_numbers}
                                />
                            </div>
                        ) : null}
                    </div>
                ) : (
                    <div className="grid max-w-xs gap-2">
                        <Label htmlFor="stock_quantity">{t('Voorraad')}</Label>
                        <Input
                            id="stock_quantity"
                            type="number"
                            min={0}
                            value={data.stock_quantity}
                            onChange={(event) =>
                                setData('stock_quantity', event.target.value)
                            }
                            placeholder="0"
                        />
                        <InputError message={errors.stock_quantity} />
                    </div>
                )}
            </AdminPanel>

            <AdminPanel
                title={t('Afbeeldingen')}
                description={t(
                    'Upload een primaire coverfoto en optionele galerijbeelden.',
                )}
            >
                <div className="grid gap-8">
                    <div className="grid gap-2">
                        <Label>{t('Primaire afbeelding')}</Label>
                        <FileUpload
                            accept="image/png,image/jpeg,image/webp"
                            maxSize={5}
                            value={data.primary_image}
                            onChange={(file) => {
                                setData(
                                    'primary_image',
                                    (file as File | null) ?? null,
                                );
                                setData('remove_primary_image', false);
                            }}
                            existingFiles={
                                showExistingPrimary && existingPrimary
                                    ? [existingPrimary]
                                    : []
                            }
                            onRemoveExisting={() =>
                                setData('remove_primary_image', true)
                            }
                            placeholder={t(
                                'Sleep een coverfoto hierheen of klik om te bladeren',
                            )}
                            hint={t('PNG, JPG of WEBP')}
                            error={errors.primary_image}
                            isUploading={isUploading}
                            uploadProgress={uploadProgress}
                            onCancel={onCancelUpload}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label>{t('Galerij')}</Label>
                        <FileUpload
                            multiple
                            accept="image/png,image/jpeg,image/webp"
                            maxSize={5}
                            maxFiles={12}
                            value={data.gallery_images}
                            onChange={(files) =>
                                setData(
                                    'gallery_images',
                                    (files as File[] | null) ?? null,
                                )
                            }
                            existingFiles={existingGallery.filter((file) =>
                                data.gallery_keep.includes(String(file.id)),
                            )}
                            onRemoveExisting={(id) =>
                                setData(
                                    'gallery_keep',
                                    data.gallery_keep.filter(
                                        (path) => path !== String(id),
                                    ),
                                )
                            }
                            placeholder={t(
                                'Sleep galerijbeelden hierheen of klik om te bladeren',
                            )}
                            hint={t('PNG, JPG of WEBP · max. 12 beelden')}
                            error={errors.gallery_images}
                            isUploading={isUploading}
                            uploadProgress={uploadProgress}
                            onCancel={onCancelUpload}
                        />
                    </div>
                </div>
            </AdminPanel>

            <AdminPanel
                title={t('Publicatie')}
                description={t(
                    'Kies de publicatiestatus en Founding Circle-toegang.',
                )}
            >
                <div className="grid gap-5">
                    <div className="grid gap-3">
                        <Label>{t('Status')}</Label>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <OptionCard
                                selected={data.is_published}
                                title={t('Gepubliceerd')}
                                description={t(
                                    'Zichtbaar op de publieke cataloguspagina’s.',
                                )}
                                onSelect={() => setData('is_published', true)}
                            />
                            <OptionCard
                                selected={!data.is_published}
                                title={t('Concept')}
                                description={t(
                                    'Alleen zichtbaar in het adminpaneel.',
                                )}
                                onSelect={() => setData('is_published', false)}
                            />
                        </div>
                    </div>

                    <div className="grid gap-3">
                        <Label>{t('Founding Circle')}</Label>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <OptionCard
                                selected={data.grants_founding_circle}
                                title={t('Geeft toegang')}
                                description={t(
                                    'Kopers krijgen toegang tot de Founding Circle.',
                                )}
                                onSelect={() =>
                                    setData('grants_founding_circle', true)
                                }
                            />
                            <OptionCard
                                selected={!data.grants_founding_circle}
                                title={t('Geen toegang')}
                                description={t(
                                    'Geen Founding Circle-rechten bij aankoop.',
                                )}
                                onSelect={() =>
                                    setData('grants_founding_circle', false)
                                }
                            />
                        </div>
                    </div>
                </div>
            </AdminPanel>
        </div>
    );
}
