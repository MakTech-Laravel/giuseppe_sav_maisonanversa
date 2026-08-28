import type { SetDataAction } from '@inertiajs/react';
import { Wand2 } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPanel } from '@/components/admin/admin-resource-shell';
import { ProductSectionEditor } from '@/components/admin/product-section-editor';
import { RepeaterField } from '@/components/admin/repeater-field';
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
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type {
    ProductFaqFormData,
    ProductSectionCatalogueEntry,
    ProductSectionFormData,
} from '@/types/admin-product';
import { emptyFaq } from '@/types/admin-product';

export type ProductFormData = {
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    status: 'active' | 'coming_soon' | 'archived';
    sort_order: string;
    amount: string;
    edition_total: string;
    edition_number_prefix: string;
    edition_number_postfix: string;
    archive_edition_numbers: number[];
    stock_quantity: string;
    is_published: boolean;
    grants_founding_circle: boolean;
    expected_delivery_label: string;
    eyebrow: string;
    hero_eyebrow: string;
    hero_subtitle: string;
    description: string;
    primary_image: File | null;
    gallery_images: File[] | null;
    remove_primary_image: boolean;
    gallery_keep: string[];
    sections: ProductSectionFormData[];
    faqs: ProductFaqFormData[];
};

export type ProductFormErrors = Partial<Record<string, string>>;

type SharedProps = {
    data: ProductFormData;
    errors: ProductFormErrors;
    setData: SetDataAction<ProductFormData>;
};

export function slugify(value: string): string {
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

export function ProductBasicsFields({ data, errors, setData }: SharedProps) {
    const { t } = useTranslation();

    return (
        <AdminPanel
            title={t('Basisgegevens')}
            description={t('Naam, slug, type en herotekst van dit product.')}
        >
            <div className="grid items-start gap-5 md:grid-cols-2">
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="name">{t('Naam')}</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        placeholder={t('bijv. Heritage No.002')}
                        autoFocus
                    />
                    <InputError message={errors.name} />
                </div>
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="slug">{t('Slug')}</Label>
                    <div className="flex gap-2">
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(event) =>
                                setData('slug', event.target.value)
                            }
                            placeholder={t('bijv. heritage-no-002')}
                            className="min-w-0 flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="shrink-0"
                            aria-label={t('Genereer slug uit naam')}
                            title={t('Genereer slug uit naam')}
                            disabled={!data.name.trim()}
                            onClick={() => setData('slug', slugify(data.name))}
                        >
                            <Wand2 className="h-4 w-4" />
                        </Button>
                    </div>
                    <InputError message={errors.slug} />
                </div>
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="type">{t('Type')}</Label>
                    <Select
                        value={data.type}
                        onValueChange={(value) =>
                            setData('type', value as ProductFormData['type'])
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
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="status">{t('Catalogusstatus')}</Label>
                    <Select
                        value={data.status}
                        onValueChange={(value) =>
                            setData('status', value as ProductFormData['status'])
                        }
                    >
                        <SelectTrigger id="status" className="w-full">
                            <SelectValue placeholder={t('Catalogusstatus')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">
                                {t('Beschikbaar')}
                            </SelectItem>
                            <SelectItem value="coming_soon">
                                {t('Binnenkort')}
                            </SelectItem>
                            <SelectItem value="archived">
                                {t('Gearchiveerd')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="sort_order">{t('Sorteervolgorde')}</Label>
                    <Input
                        id="sort_order"
                        type="number"
                        value={data.sort_order}
                        onChange={(event) =>
                            setData('sort_order', event.target.value)
                        }
                        placeholder="0"
                    />
                    <InputError message={errors.sort_order} />
                </div>
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="eyebrow">{t('Productlabel')}</Label>
                    <Input
                        id="eyebrow"
                        value={data.eyebrow}
                        onChange={(event) =>
                            setData('eyebrow', event.target.value)
                        }
                        placeholder={t('bijv. Maison Anversa · Founding Edition')}
                    />
                    <InputError message={errors.eyebrow} />
                </div>
                <div className="grid min-w-0 gap-2 md:col-span-2">
                    <Label htmlFor="hero_eyebrow">{t('Hero-eyebrow')}</Label>
                    <Input
                        id="hero_eyebrow"
                        value={data.hero_eyebrow}
                        onChange={(event) =>
                            setData('hero_eyebrow', event.target.value)
                        }
                        placeholder={t(
                            'bijv. Founding Edition · 100 Stuks Wereldwijd',
                        )}
                    />
                    <InputError message={errors.hero_eyebrow} />
                </div>
                <div className="grid min-w-0 gap-2 md:col-span-2">
                    <Label htmlFor="hero_subtitle">{t('Hero-ondertitel')}</Label>
                    <Textarea
                        id="hero_subtitle"
                        value={data.hero_subtitle}
                        onChange={(event) =>
                            setData('hero_subtitle', event.target.value)
                        }
                        className="min-h-24 resize-y"
                    />
                    <InputError message={errors.hero_subtitle} />
                </div>
                <div className="grid min-w-0 gap-2 md:col-span-2">
                    <Label htmlFor="description">
                        {t('Productbeschrijving')}
                    </Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        className="min-h-32 resize-y"
                    />
                    <InputError message={errors.description} />
                </div>
            </div>
            <p className="mt-5 text-xs text-muted-foreground">
                {t(
                    'Tekst op dit formulier is de bron. DeepL vult NL, EN en FR na opslaan. Pas per taal aan via Vertalingen.',
                )}
            </p>
        </AdminPanel>
    );
}

export function ProductPricingFields({ data, errors, setData }: SharedProps) {
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

    const toggleArchive = (number: number, checked: boolean) => {
        const next = checked
            ? [...data.archive_edition_numbers, number].sort((a, b) => a - b)
            : data.archive_edition_numbers.filter((value) => value !== number);

        setData('archive_edition_numbers', next);
    };

    const handleEditionTotalChange = (value: string) => {
        const total = Math.min(
            Math.max(0, Number.parseInt(value || '0', 10) || 0),
            10000,
        );

        setData((current) => ({
            ...current,
            edition_total: value,
            archive_edition_numbers: current.archive_edition_numbers.filter(
                (number) => number <= total,
            ),
        }));
    };

    return (
        <div className="grid w-full gap-6">
            <AdminPanel
                title={t('Prijs & levering')}
                description={t('Verkoopprijs en verwachte leveringsbelofte.')}
            >
                <div className="grid items-start gap-5 md:grid-cols-2">
                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="amount">{t('Bedrag')}</Label>
                        <div className="relative">
                            <Input
                                id="amount"
                                type="text"
                                inputMode="decimal"
                                value={data.amount}
                                onChange={(event) =>
                                    setData('amount', event.target.value)
                                }
                                placeholder="249.00"
                                className="pr-12"
                            />
                            <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                EUR
                            </span>
                        </div>
                        <InputError message={errors.amount} />
                    </div>
                    <div className="grid min-w-0 gap-2">
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

                <div className="mt-5 grid gap-3">
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
                                {/* contain-strict: Chromium otherwise lets this tall grid inflate page scrollHeight */}
                                <div className="h-96 overflow-hidden rounded-lg border bg-muted/20 contain-strict">
                                    <div className="h-full scrollbar-none overflow-y-auto p-3">
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
        </div>
    );
}

export function ProductMediaFields({
    data,
    errors,
    setData,
    existingPrimary = null,
    existingGallery = [],
    isUploading = false,
    uploadProgress = null,
    onCancelUpload,
}: SharedProps & {
    existingPrimary?: ExistingFile | null;
    existingGallery?: ExistingFile[];
    isUploading?: boolean;
    uploadProgress?: number | null;
    onCancelUpload?: () => void;
}) {
    const { t } = useTranslation();
    const showExistingPrimary =
        Boolean(existingPrimary) &&
        !data.primary_image &&
        !data.remove_primary_image;

    return (
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
                        accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                        maxSize={5}
                        value={data.primary_image}
                        onChange={(file) => {
                            setData((current) => ({
                                ...current,
                                primary_image: (file as File | null) ?? null,
                                remove_primary_image: false,
                            }));
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
                        accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                        maxSize={5}
                        maxFiles={12}
                        value={data.gallery_images}
                        onChange={(files) =>
                            setData((current) => ({
                                ...current,
                                gallery_images: (files as File[] | null) ?? null,
                            }))
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
    );
}

type StoredProductSection = {
    key: string;
    existing_image?: string | null;
};

export function ProductSectionFields({
    data,
    setData,
    catalogue,
    storedSections = [],
}: SharedProps & {
    catalogue: ProductSectionCatalogueEntry[];
    storedSections?: StoredProductSection[];
}) {
    const { t } = useTranslation();

    const patchSection = (
        key: string,
        patch: Partial<ProductSectionFormData>,
    ) => {
        setData(
            'sections',
            data.sections.map((section) =>
                section.key === key ? { ...section, ...patch } : section,
            ),
        );
    };

    const moveSection = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= data.sections.length) {
            return;
        }

        const next = [...data.sections];
        const [moved] = next.splice(index, 1);
        next.splice(target, 0, moved);

        setData(
            'sections',
            next.map((section, at) => ({ ...section, sort_order: at })),
        );
    };

    return (
        <AdminPanel
            title={t('Paginasecties')}
            description={t(
                'Beheer elke sectie van de publieke productpagina: kopteksten, onderdelen, zichtbaarheid en volgorde.',
            )}
        >
            <div className="grid gap-3">
                {data.sections.map((section, index) => {
                    const entry = catalogue.find(
                        (candidate) => candidate.key === section.key,
                    );

                    if (!entry) {
                        return null;
                    }

                    return (
                        <ProductSectionEditor
                            key={section.key}
                            entry={entry}
                            section={section}
                            storedExistingImage={
                                storedSections.find(
                                    (stored) => stored.key === section.key,
                                )?.existing_image ?? null
                            }
                            onChange={(patch) => patchSection(section.key, patch)}
                            onMove={(direction) => moveSection(index, direction)}
                            canMoveUp={index > 0}
                            canMoveDown={index < data.sections.length - 1}
                        />
                    );
                })}
            </div>
        </AdminPanel>
    );
}

export function ProductFaqFields({ data, setData }: SharedProps) {
    const { t } = useTranslation();

    return (
        <AdminPanel
            title={t('Veelgestelde vragen')}
            description={t(
                'Vragen en antwoorden die alleen op dit product worden getoond.',
            )}
        >
            <RepeaterField<ProductFaqFormData>
                rows={data.faqs}
                onChange={(faqs) => setData('faqs', faqs)}
                makeRow={emptyFaq}
                rowKey={(row) => row.uid}
                addLabel="Vraag toevoegen"
                emptyLabel="Nog geen vragen voor dit product."
                renderRow={(row, _index, update) => (
                    <div className="grid gap-3">
                        <div className="grid gap-1.5">
                            <Label className="text-xs">{t('Vraag')}</Label>
                            <Input
                                value={row.question}
                                onChange={(event) =>
                                    update({ question: event.target.value })
                                }
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">{t('Antwoord')}</Label>
                            <Textarea
                                value={row.answer}
                                onChange={(event) =>
                                    update({ answer: event.target.value })
                                }
                                className="min-h-24 resize-y"
                            />
                        </div>
                        <label className="flex w-fit cursor-pointer items-center gap-2 text-xs">
                            <Checkbox
                                checked={row.is_published}
                                onCheckedChange={(checked) =>
                                    update({ is_published: checked === true })
                                }
                            />
                            {t('Gepubliceerd')}
                        </label>
                    </div>
                )}
            />
        </AdminPanel>
    );
}

export function ProductPublishFields({ data, setData }: SharedProps) {
    const { t } = useTranslation();
    const visibleSections = data.sections.filter(
        (section) => section.is_visible,
    ).length;

    return (
        <AdminPanel
            title={t('Publicatie')}
            description={t('Controleer de samenvatting en publiceer.')}
        >
            <div className="grid gap-5">
                <dl className="grid gap-3 rounded-lg border bg-muted/20 p-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            {t('Naam')}
                        </dt>
                        <dd className="font-medium">{data.name || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            {t('Slug')}
                        </dt>
                        <dd className="font-mono text-xs">{data.slug || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            {t('Bedrag')}
                        </dt>
                        <dd className="font-medium">{data.amount || '—'} EUR</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            {t('Zichtbare secties')}
                        </dt>
                        <dd className="font-medium">{visibleSections}</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            {t('Veelgestelde vragen')}
                        </dt>
                        <dd className="font-medium">{data.faqs.length}</dd>
                    </div>
                </dl>

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
                            description={t('Alleen zichtbaar in het adminpaneel.')}
                            onSelect={() => setData('is_published', false)}
                        />
                    </div>
                </div>
            </div>
        </AdminPanel>
    );
}
