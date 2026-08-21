import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
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

export type ProductFormData = {
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    amount: string;
    edition_total: string;
    archive_edition_numbers: string;
    stock_quantity: string;
    is_published: boolean;
    grants_founding_circle: boolean;
    expected_delivery_label: string;
};

export function ProductFormFields({
    data,
    errors,
    setData,
}: {
    data: ProductFormData;
    errors: Partial<Record<keyof ProductFormData, string>>;
    setData: <K extends keyof ProductFormData>(
        key: K,
        value: ProductFormData[K],
    ) => void;
}) {
    const { t } = useTranslation();
    const isLimited = data.type === 'limited_edition';

    return (
        <div className="grid gap-5">
            <div className="grid gap-2">
                <Label htmlFor="name">{t('Naam')}</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    autoFocus
                />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="slug">{t('Slug')}</Label>
                <Input
                    id="slug"
                    value={data.slug}
                    onChange={(event) => setData('slug', event.target.value)}
                />
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
                    <SelectTrigger
                        id="type"
                        className="w-full border-gold/25 bg-choc/50 text-cream data-placeholder:text-stone"
                    >
                        <SelectValue placeholder={t('Type')} />
                    </SelectTrigger>
                    <SelectContent className="border-gold/25 bg-choc text-cream">
                        <SelectItem
                            value="simple"
                            className="text-cream focus:bg-gold/15 focus:text-cream"
                        >
                            {t('Eenvoudige voorraad')}
                        </SelectItem>
                        <SelectItem
                            value="limited_edition"
                            className="text-cream focus:bg-gold/15 focus:text-cream"
                        >
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
                    onChange={(event) => setData('amount', event.target.value)}
                />
                <p className="text-xs text-muted-foreground">EUR</p>
                <InputError message={errors.amount} />
            </div>
            {isLimited ? (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="edition_total">{t('Editiegrootte')}</Label>
                        <Input
                            id="edition_total"
                            type="number"
                            min={1}
                            value={data.edition_total}
                            onChange={(event) =>
                                setData('edition_total', event.target.value)
                            }
                        />
                        <InputError message={errors.edition_total} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="archive_edition_numbers">
                            {t('Archiefnummers')}
                        </Label>
                        <Input
                            id="archive_edition_numbers"
                            value={data.archive_edition_numbers}
                            onChange={(event) =>
                                setData(
                                    'archive_edition_numbers',
                                    event.target.value,
                                )
                            }
                            placeholder="1"
                        />
                        <p className="text-xs text-muted-foreground">
                            {t('Komma-gescheiden. Leeg = alles verkoopbaar.')}
                        </p>
                        <InputError message={errors.archive_edition_numbers} />
                    </div>
                </>
            ) : (
                <div className="grid gap-2">
                    <Label htmlFor="stock_quantity">{t('Voorraad')}</Label>
                    <Input
                        id="stock_quantity"
                        type="number"
                        min={0}
                        value={data.stock_quantity}
                        onChange={(event) =>
                            setData('stock_quantity', event.target.value)
                        }
                    />
                    <InputError message={errors.stock_quantity} />
                </div>
            )}
            <div className="grid gap-2">
                <Label htmlFor="expected_delivery_label">
                    {t('Verwachte levering')}
                </Label>
                <Input
                    id="expected_delivery_label"
                    value={data.expected_delivery_label}
                    onChange={(event) =>
                        setData('expected_delivery_label', event.target.value)
                    }
                />
                <InputError message={errors.expected_delivery_label} />
            </div>
            <label className="flex items-center gap-2 text-sm">
                <Checkbox
                    checked={data.is_published}
                    onCheckedChange={(checked) =>
                        setData('is_published', checked === true)
                    }
                />
                {t('Gepubliceerd')}
            </label>
            <label className="flex items-center gap-2 text-sm">
                <Checkbox
                    checked={data.grants_founding_circle}
                    onCheckedChange={(checked) =>
                        setData('grants_founding_circle', checked === true)
                    }
                />
                {t('Geeft toegang tot Founding Circle')}
            </label>
        </div>
    );
}
