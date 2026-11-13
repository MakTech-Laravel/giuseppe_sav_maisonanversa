import { Head, useForm } from '@inertiajs/react';
import { Loader2, Store, Settings2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import storefrontAccess from '@/routes/admin/storefront-access';

interface StorefrontAccessForm {
    purchases_enabled: boolean;
    registration_enabled: boolean;
}

export default function StorefrontAccessEdit({
    settings,
    envPurchasesEnabled,
    envRegistrationEnabled,
}: {
    settings: StorefrontAccessForm;
    envPurchasesEnabled: boolean;
    envRegistrationEnabled: boolean;
}) {
    const { t } = useTranslation();
    const form = useForm(storefrontAccess.update(wayfinderLocale()), {
        purchases_enabled: settings.purchases_enabled,
        registration_enabled: settings.registration_enabled,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
    };

    return (
        <>
            <Head title={t('Storefront access')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Storefront access')}
                    description={t(
                        'Turn purchases and registration on or off. The .env kill switch must also be on.',
                    )}
                    icon={Store}
                />
                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        aside={
                            <AdminPanel title={t('Save')}>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="w-full"
                                >
                                    {form.processing ? (
                                        <Loader2 className="size-4 animate-spin" />
                                    ) : (
                                        <Settings2 className="size-4" />
                                    )}
                                    {t('Save settings')}
                                </Button>
                            </AdminPanel>
                        }
                    >
                        <AdminPanel title={t('Access')}>
                            <div className="space-y-5">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="purchases_enabled"
                                            checked={
                                                form.data.purchases_enabled
                                            }
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'purchases_enabled',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="purchases_enabled">
                                            {t('Enable purchases')}
                                        </Label>
                                    </div>
                                    <InputError
                                        message={
                                            form.errors.purchases_enabled
                                        }
                                    />
                                    {!envPurchasesEnabled && (
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'MAISON_CHECKOUT_ENABLED is off in .env — purchases stay paused until it is turned back on.',
                                            )}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="registration_enabled"
                                            checked={
                                                form.data.registration_enabled
                                            }
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'registration_enabled',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="registration_enabled">
                                            {t('Enable registration')}
                                        </Label>
                                    </div>
                                    <InputError
                                        message={
                                            form.errors.registration_enabled
                                        }
                                    />
                                    {!envRegistrationEnabled && (
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'MAISON_REGISTRATION_ENABLED is off in .env — registration stays paused until it is turned back on.',
                                            )}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </AdminPanel>
                    </AdminResourceShell>
                </form>
            </div>
        </>
    );
}

StorefrontAccessEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Storefront-toegang',
            href: storefrontAccess.edit(wayfinderLocale()),
        },
    ],
};
