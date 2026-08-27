import { ChevronDownIcon, EyeIcon, EyeOffIcon } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RepeaterField } from '@/components/admin/repeater-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type {
    ProductSectionCatalogueEntry,
    ProductSectionFormData,
    ProductSectionItemFormData,
} from '@/types/admin-product';
import { emptySectionItem } from '@/types/admin-product';

const ITEM_FIELD_LABELS: Record<string, string> = {
    number_label: 'Nummer',
    icon: 'Icoon',
    title: 'Titel',
    body: 'Tekst',
};

function ItemFields({
    fields,
    row,
    update,
}: {
    fields: string[];
    row: ProductSectionItemFormData;
    update: (patch: Partial<ProductSectionItemFormData>) => void;
}) {
    const { t } = useTranslation();
    const compact = fields.filter((field) => field !== 'body');

    return (
        <div className="grid gap-3">
            {compact.length > 0 ? (
                <div className="grid gap-3 sm:grid-cols-3">
                    {compact.map((field) => (
                        <div key={field} className="grid gap-1.5">
                            <Label className="text-xs">
                                {t(ITEM_FIELD_LABELS[field] ?? field)}
                            </Label>
                            <Input
                                value={
                                    row[
                                        field as keyof ProductSectionItemFormData
                                    ] as string
                                }
                                onChange={(event) =>
                                    update({ [field]: event.target.value })
                                }
                            />
                        </div>
                    ))}
                </div>
            ) : null}

            {fields.includes('body') ? (
                <div className="grid gap-1.5">
                    <Label className="text-xs">{t('Tekst')}</Label>
                    <Textarea
                        value={row.body}
                        onChange={(event) => update({ body: event.target.value })}
                        className="min-h-20 resize-y"
                    />
                </div>
            ) : null}
        </div>
    );
}

export function ProductSectionEditor({
    entry,
    section,
    onChange,
    onMove,
    canMoveUp,
    canMoveDown,
}: {
    entry: ProductSectionCatalogueEntry;
    section: ProductSectionFormData;
    onChange: (patch: Partial<ProductSectionFormData>) => void;
    onMove: (direction: -1 | 1) => void;
    canMoveUp: boolean;
    canMoveDown: boolean;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    return (
        <div
            className={cn(
                'rounded-xl border bg-card',
                section.is_visible ? undefined : 'opacity-70',
            )}
        >
            <div className="flex flex-wrap items-center gap-3 border-b bg-muted/30 px-4 py-3">
                <button
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    className="flex min-w-0 flex-1 items-center gap-2 text-left"
                >
                    <ChevronDownIcon
                        className={cn(
                            'size-4 shrink-0 text-muted-foreground transition-transform',
                            open ? 'rotate-180' : undefined,
                        )}
                    />
                    <span className="min-w-0">
                        <span className="block truncate text-sm font-medium">
                            {t(entry.label)}
                        </span>
                        <span className="block truncate text-xs text-muted-foreground">
                            {entry.uses_items
                                ? t('{{count}} onderdelen', {
                                      count: section.items.length,
                                  })
                                : t(entry.description)}
                        </span>
                    </span>
                </button>

                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        size="sm"
                        variant={section.is_visible ? 'ghost' : 'secondary'}
                        onClick={() =>
                            onChange({ is_visible: !section.is_visible })
                        }
                    >
                        {section.is_visible ? (
                            <EyeIcon className="size-4" />
                        ) : (
                            <EyeOffIcon className="size-4" />
                        )}
                        {section.is_visible ? t('Zichtbaar') : t('Verborgen')}
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        disabled={!canMoveUp}
                        aria-label={t('Omhoog verplaatsen')}
                        onClick={() => onMove(-1)}
                    >
                        ↑
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        disabled={!canMoveDown}
                        aria-label={t('Omlaag verplaatsen')}
                        onClick={() => onMove(1)}
                    >
                        ↓
                    </Button>
                </div>
            </div>

            {open ? (
                <div className="grid gap-5 p-4 sm:p-5">
                    <p className="text-xs text-muted-foreground">
                        {t(entry.description)}
                    </p>

                    {entry.uses_heading ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label className="text-xs">{t('Eyebrow')}</Label>
                                <Input
                                    value={section.eyebrow}
                                    onChange={(event) =>
                                        onChange({ eyebrow: event.target.value })
                                    }
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label className="text-xs">{t('Titel')}</Label>
                                <Input
                                    value={section.heading}
                                    onChange={(event) =>
                                        onChange({ heading: event.target.value })
                                    }
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label className="text-xs">
                                    {t('Tweede titelregel')}
                                </Label>
                                <Input
                                    value={section.subheading}
                                    onChange={(event) =>
                                        onChange({
                                            subheading: event.target.value,
                                        })
                                    }
                                />
                            </div>
                            {entry.uses_image ? (
                                <div className="grid gap-1.5">
                                    <Label className="text-xs">
                                        {t('Afbeeldingssleutel')}
                                    </Label>
                                    <Input
                                        value={section.image_key}
                                        onChange={(event) =>
                                            onChange({
                                                image_key: event.target.value,
                                            })
                                        }
                                        placeholder="atelier-workshop"
                                    />
                                </div>
                            ) : null}
                            <div className="grid gap-1.5 md:col-span-2">
                                <Label className="text-xs">
                                    {t('Introductietekst')}
                                </Label>
                                <Textarea
                                    value={section.intro}
                                    onChange={(event) =>
                                        onChange({ intro: event.target.value })
                                    }
                                    className="min-h-20 resize-y"
                                />
                            </div>
                        </div>
                    ) : null}

                    {entry.key === 'related' ? (
                        <Button
                            type="button"
                            size="sm"
                            variant={
                                section.include_house_card
                                    ? 'secondary'
                                    : 'outline'
                            }
                            className="w-fit"
                            onClick={() =>
                                onChange({
                                    include_house_card:
                                        !section.include_house_card,
                                })
                            }
                        >
                            {section.include_house_card
                                ? t('"Het Huis"-kaart wordt getoond')
                                : t('"Het Huis"-kaart verbergen')}
                        </Button>
                    ) : null}

                    {entry.uses_items ? (
                        <RepeaterField<ProductSectionItemFormData>
                            rows={section.items}
                            onChange={(items) => onChange({ items })}
                            makeRow={emptySectionItem}
                            rowKey={(row) => row.uid}
                            addLabel="Onderdeel toevoegen"
                            emptyLabel="Nog geen onderdelen."
                            renderRow={(row, _index, update) => (
                                <ItemFields
                                    fields={entry.item_fields}
                                    row={row}
                                    update={update}
                                />
                            )}
                        />
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
