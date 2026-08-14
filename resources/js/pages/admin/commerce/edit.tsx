import { Head, useForm } from '@inertiajs/react';
import { Loader2, Settings2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import commerce from '@/routes/admin/commerce';

interface CommerceSettings {
    shipping_estimate_min: string;
    shipping_estimate_max: string;
    shipping_eu_included: boolean;
    default_expected_delivery_label: string | null;
    prices_include_tax: boolean;
}

export default function CommerceSettingsEdit({
    settings,
}: {
    settings: CommerceSettings;
}) {
    const { t } = useTranslation();
    const form = useForm(commerce.update(wayfinderLocale()), {
        shipping_estimate_min: settings.shipping_estimate_min,
        shipping_estimate_max: settings.shipping_estimate_max,
        shipping_eu_included: settings.shipping_eu_included,
        default_expected_delivery_label:
            settings.default_expected_delivery_label ?? '',
        prices_include_tax: settings.prices_include_tax,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
    };

    return (
        <>
            <Head title={t('Handelsinstellingen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Handelsinstellingen')}
                    description={t(
                        'Verzendschatting en leveringscopy — nog geen Stripe-regel tot bevestiging.',
                    )}
                    icon={Settings2}
                />
                <form
                    onSubmit={submit}
                    className="w-full max-w-2xl space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="shipping_estimate_min">
                                {t('Verzending vanaf (€)')}
                            </Label>
                            <Input
                                id="shipping_estimate_min"
                                value={form.data.shipping_estimate_min}
                                onChange={(event) =>
                                    form.setData(
                                        'shipping_estimate_min',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.shipping_estimate_min}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="shipping_estimate_max">
                                {t('Verzending tot (€)')}
                            </Label>
                            <Input
                                id="shipping_estimate_max"
                                value={form.data.shipping_estimate_max}
                                onChange={(event) =>
                                    form.setData(
                                        'shipping_estimate_max',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.shipping_estimate_max}
                            />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.shipping_eu_included}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'shipping_eu_included',
                                    checked === true,
                                )
                            }
                        />
                        {t('Verzending inbegrepen in de EU')}
                    </label>
                    <div className="grid gap-2">
                        <Label htmlFor="default_expected_delivery_label">
                            {t('Standaard leveringscopy')}
                        </Label>
                        <Input
                            id="default_expected_delivery_label"
                            value={form.data.default_expected_delivery_label}
                            onChange={(event) =>
                                form.setData(
                                    'default_expected_delivery_label',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={form.errors.default_expected_delivery_label}
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.prices_include_tax}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'prices_include_tax',
                                    checked === true,
                                )
                            }
                        />
                        {t('Prijzen zijn inclusief belasting')}
                    </label>
                    <p className="text-xs text-muted-foreground">
                        {t(
                            'Geen bestemmings-btw of niet-restitueerbare pre-orderregel tot de accountant dat vastlegt.',
                        )}
                    </p>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Instellingen opslaan')}
                    </Button>
                </form>
            </div>
        </>
    );
}

CommerceSettingsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Handelsinstellingen', href: commerce.edit(wayfinderLocale()) },
    ],
};
