import { Head, useForm } from '@inertiajs/react';
import { Globe, Loader2, Settings2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import siteSettings from '@/routes/admin/site-settings';

interface SiteSettingsForm {
    phone: string;
    whatsapp: string;
    email_hello: string;
    email_press: string;
    instagram_url: string;
    boutique_lat: string;
    boutique_lng: string;
}

export default function SiteSettingsEdit({
    settings,
}: {
    settings: SiteSettingsForm;
}) {
    const { t } = useTranslation();
    const form = useForm(siteSettings.update(wayfinderLocale()), {
        phone: settings.phone,
        whatsapp: settings.whatsapp,
        email_hello: settings.email_hello,
        email_press: settings.email_press,
        instagram_url: settings.instagram_url,
        boutique_lat: settings.boutique_lat,
        boutique_lng: settings.boutique_lng,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
    };

    return (
        <>
            <Head title={t('Site-instellingen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Site-instellingen')}
                    description={t(
                        'Contactkanalen, socialemedia-links en de locatie van de boutique voor het hele huis.',
                    )}
                    icon={Globe}
                />
                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        aside={
                            <AdminPanel title={t('Opslaan')}>
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
                                    {t('Instellingen opslaan')}
                                </Button>
                            </AdminPanel>
                        }
                    >
                        <AdminPanel
                            title={t('Contact')}
                            description={t(
                                'Telefoon, WhatsApp en e-mailadressen voor het huis.',
                            )}
                        >
                            <div className="grid gap-5">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">
                                            {t('Telefoon')}
                                        </Label>
                                        <Input
                                            id="phone"
                                            value={form.data.phone}
                                            onChange={(event) =>
                                                form.setData(
                                                    'phone',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.phone}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="whatsapp">
                                            {t('WhatsApp')}
                                        </Label>
                                        <Input
                                            id="whatsapp"
                                            value={form.data.whatsapp}
                                            onChange={(event) =>
                                                form.setData(
                                                    'whatsapp',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.whatsapp}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email_hello">
                                            {t('E-mail algemeen')}
                                        </Label>
                                        <Input
                                            id="email_hello"
                                            type="email"
                                            value={form.data.email_hello}
                                            onChange={(event) =>
                                                form.setData(
                                                    'email_hello',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.email_hello}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email_press">
                                            {t('E-mail pers')}
                                        </Label>
                                        <Input
                                            id="email_press"
                                            type="email"
                                            value={form.data.email_press}
                                            onChange={(event) =>
                                                form.setData(
                                                    'email_press',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.email_press}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="instagram_url">
                                        {t('Instagram-URL')}
                                    </Label>
                                    <Input
                                        id="instagram_url"
                                        value={form.data.instagram_url}
                                        onChange={(event) =>
                                            form.setData(
                                                'instagram_url',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.instagram_url}
                                    />
                                </div>
                            </div>
                        </AdminPanel>

                        <AdminPanel
                            title={t('Boutique-locatie')}
                            description={t(
                                'Coördinaten voor de boutique-kaart op de contactpagina.',
                            )}
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="boutique_lat">
                                        {t('Breedtegraad van de boutique')}
                                    </Label>
                                    <Input
                                        id="boutique_lat"
                                        value={form.data.boutique_lat}
                                        onChange={(event) =>
                                            form.setData(
                                                'boutique_lat',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.boutique_lat}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="boutique_lng">
                                        {t('Lengtegraad van de boutique')}
                                    </Label>
                                    <Input
                                        id="boutique_lng"
                                        value={form.data.boutique_lng}
                                        onChange={(event) =>
                                            form.setData(
                                                'boutique_lng',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.boutique_lng}
                                    />
                                </div>
                            </div>
                        </AdminPanel>
                    </AdminResourceShell>
                </form>
            </div>
        </>
    );
}

SiteSettingsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Site-instellingen',
            href: siteSettings.edit(wayfinderLocale()),
        },
    ],
};
