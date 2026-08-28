import { Head, useForm } from '@inertiajs/react';
import { Globe, Loader2, Settings2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { SiteSettingTranslationsDialog } from '@/components/admin/site-setting-translations-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import siteSettings from '@/routes/admin/site-settings';

interface SiteSettingsForm {
    phone: string;
    whatsapp: string;
    email_hello: string;
    email_press: string;
    instagram_url: string;
    boutique_lat: string;
    boutique_lng: string;
    announcement_text: string | null;
}

type LocaleCopy = {
    announcement_text: string;
};

type TranslationStatus = {
    announcement_text: boolean;
};

export default function SiteSettingsEdit({
    settings,
    locales,
    translations,
    translationStatus,
}: {
    settings: SiteSettingsForm;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
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
        announcement_text: settings.announcement_text ?? '',
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
                        'Contactkanalen, social links en boutique-locatie voor het hele huis.',
                    )}
                    icon={Globe}
                />
                <form
                    onSubmit={submit}
                    className="w-full max-w-2xl space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="phone">{t('Telefoon')}</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                            />
                            <InputError message={form.errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="whatsapp">{t('WhatsApp')}</Label>
                            <Input
                                id="whatsapp"
                                value={form.data.whatsapp}
                                onChange={(event) =>
                                    form.setData('whatsapp', event.target.value)
                                }
                            />
                            <InputError message={form.errors.whatsapp} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="email_hello">{t('E-mail algemeen')}</Label>
                            <Input
                                id="email_hello"
                                type="email"
                                value={form.data.email_hello}
                                onChange={(event) =>
                                    form.setData('email_hello', event.target.value)
                                }
                            />
                            <InputError message={form.errors.email_hello} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email_press">{t('E-mail pers')}</Label>
                            <Input
                                id="email_press"
                                type="email"
                                value={form.data.email_press}
                                onChange={(event) =>
                                    form.setData('email_press', event.target.value)
                                }
                            />
                            <InputError message={form.errors.email_press} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="instagram_url">{t('Instagram URL')}</Label>
                        <Input
                            id="instagram_url"
                            value={form.data.instagram_url}
                            onChange={(event) =>
                                form.setData('instagram_url', event.target.value)
                            }
                        />
                        <InputError message={form.errors.instagram_url} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="boutique_lat">{t('Boutique latitude')}</Label>
                            <Input
                                id="boutique_lat"
                                value={form.data.boutique_lat}
                                onChange={(event) =>
                                    form.setData('boutique_lat', event.target.value)
                                }
                            />
                            <InputError message={form.errors.boutique_lat} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="boutique_lng">{t('Boutique longitude')}</Label>
                            <Input
                                id="boutique_lng"
                                value={form.data.boutique_lng}
                                onChange={(event) =>
                                    form.setData('boutique_lng', event.target.value)
                                }
                            />
                            <InputError message={form.errors.boutique_lng} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="announcement_text">
                            {t('Aankondiging (topbar, optioneel)')}
                        </Label>
                        <Input
                            id="announcement_text"
                            value={form.data.announcement_text}
                            onChange={(event) =>
                                form.setData('announcement_text', event.target.value)
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Tekst op dit formulier is de Nederlandse bron. DeepL vult EN en FR na opslaan.',
                            )}
                        </p>
                        <InputError message={form.errors.announcement_text} />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Settings2 className="size-4" />
                            )}
                            {t('Opslaan')}
                        </Button>
                        <SiteSettingTranslationsDialog
                            locales={locales}
                            translations={translations}
                            translationStatus={translationStatus}
                        />
                    </div>
                </form>
            </div>
        </>
    );
}

SiteSettingsEdit.layout = {
    title: 'Site-instellingen',
};
