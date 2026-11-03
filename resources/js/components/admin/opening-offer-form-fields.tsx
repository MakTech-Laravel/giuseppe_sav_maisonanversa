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
                    'Het product dat bezoekers in de opening kunnen reserveren.',
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
                            <SelectValue placeholder={t('Kies een product')} />
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
                            'Alleen gepubliceerde, actieve producten met een genummerde oplage.',
                        )}
                    </p>
                    <InputError message={errors.product_id} />
                </div>
            </AdminPanel>

            <AdminPanel
                title={t('Instellingen')}
                description={t(
                    'Of het product nu in de opening staat, en in welke volgorde.',
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
                                    ? t('Actief in de opening')
                                    : t('Inactief')}
                            </span>
                        </label>
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Slechts één aanbod is tegelijk actief: activeren zet de andere uit.',
                            )}
                        </p>
                        <InputError message={errors.is_active} />
                    </div>

                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="sort_order">{t('Volgorde')}</Label>
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
                                'Lager = eerder. Bij meerdere actieve aanbiedingen wint de laagste.',
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
                        'De nummers in de opening zijn de echte editienummers van dit product: beschikbare nummers staan als beschikbaar, alle andere als gereserveerd.',
                    )}
                </li>
                <li>
                    {t(
                        'De koopprocedure blijft hetzelfde: inloggen, een nummer kiezen en afrekenen.',
                    )}
                </li>
                <li>
                    {t(
                        'Alleen Heritage No.001 geeft lidmaatschap van de Founding Circle.',
                    )}
                </li>
                <li>
                    {t(
                        'Is er geen actief aanbod, of is het product niet meer zichtbaar, dan toont de opening geen nummers.',
                    )}
                </li>
            </ul>
        </AdminPanel>
    );
}
