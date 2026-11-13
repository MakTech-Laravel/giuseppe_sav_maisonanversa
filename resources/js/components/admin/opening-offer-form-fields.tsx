import { useTranslation } from 'react-i18next';
import { AdminPanel } from '@/components/admin/admin-resource-shell';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type OpeningOfferFormData = {
    product_id: number | null;
    is_active: boolean;
    sort_order: number;
};

export type OpeningOfferFormErrors = Partial<
    Record<keyof OpeningOfferFormData, string>
>;

export type OpeningOfferProductOption = {
    id: number;
    name: string;
    slug: string;
    edition_total: number | null;
};

export function OpeningOfferFormFields({
    data,
    errors,
    products,
    setData,
}: {
    data: OpeningOfferFormData;
    errors: OpeningOfferFormErrors;
    products: OpeningOfferProductOption[];
    setData: <K extends keyof OpeningOfferFormData>(
        key: K,
        value: OpeningOfferFormData[K],
    ) => void;
}) {
    const { t } = useTranslation();

    return (
        <div className="grid w-full gap-6">
            <AdminPanel
                title={t('Product')}
                description={t(
                    'The product visitors can reserve in the opening.',
                )}
            >
                <div className="grid gap-2">
                    <Label htmlFor="product_id">{t('Product')}</Label>
                    <Select
                        value={
                            data.product_id === null
                                ? ''
                                : String(data.product_id)
                        }
                        onValueChange={(value) =>
                            setData('product_id', Number(value))
                        }
                    >
                        <SelectTrigger id="product_id" className="w-full">
                            <SelectValue placeholder={t('Choose a product')} />
                        </SelectTrigger>
                        <SelectContent>
                            {products.map((product) => (
                                <SelectItem
                                    key={product.id}
                                    value={String(product.id)}
                                >
                                    {product.name}
                                    {product.edition_total !== null
                                        ? ` · ${product.edition_total}`
                                        : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <p className="text-xs text-muted-foreground">
                        {t(
                            'Only published, active products with a numbered edition.',
                        )}
                    </p>
                    <InputError message={errors.product_id} />
                </div>
            </AdminPanel>

            <AdminPanel
                title={t('Settings')}
                description={t(
                    'Whether the product is in the opening now, and in which order.',
                )}
            >
                <div className="grid items-start gap-5 sm:grid-cols-2">
                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="is_active">{t('Status')}</Label>
                        <label
                            htmlFor="is_active"
                            className="flex min-h-9 cursor-pointer items-center gap-3 rounded-md border px-3 py-2 text-sm"
                        >
                            <input
                                id="is_active"
                                type="checkbox"
                                className="size-4 accent-primary"
                                checked={data.is_active}
                                onChange={(event) =>
                                    setData('is_active', event.target.checked)
                                }
                            />
                            <span>
                                {data.is_active
                                    ? t('Active in the opening')
                                    : t('Inactive')}
                            </span>
                        </label>
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Only one offer is active at a time: activating one turns the others off.',
                            )}
                        </p>
                        <InputError message={errors.is_active} />
                    </div>

                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="sort_order">{t('Order')}</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(event) =>
                                setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Lower = earlier. With several active offers, the lowest wins.',
                            )}
                        </p>
                        <InputError message={errors.sort_order} />
                    </div>
                </div>
            </AdminPanel>
        </div>
    );
}

export function OpeningOfferFormAside() {
    const { t } = useTranslation();

    return (
        <AdminPanel title={t('Tips')}>
            <ul className="space-y-3 text-sm text-muted-foreground">
                <li>
                    {t(
                        'The numbers in the opening are this product's real edition numbers: available numbers show as available, all others as reserved.',
                    )}
                </li>
                <li>
                    {t(
                        'The purchase procedure stays the same: log in, choose a number and check out.',
                    )}
                </li>
                <li>
                    {t(
                        'Only Heritage Nº 001 grants Founding Circle membership.',
                    )}
                </li>
                <li>
                    {t(
                        'With no active offer, or when the product is no longer visible, the opening shows no numbers.',
                    )}
                </li>
            </ul>
        </AdminPanel>
    );
}
