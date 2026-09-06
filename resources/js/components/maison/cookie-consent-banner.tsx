import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { store as cookieConsentStore } from '@/routes/cookie-consent';

export function CookieConsentBanner() {
    const { t } = useTranslation();
    const { cookieConsent } = usePage<{ cookieConsent?: string | null }>()
        .props;
    const form = useForm({
        necessary: true,
        analytics: false,
        marketing: false,
    });

    if (cookieConsent) {
        return null;
    }

    return (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-gold/30 bg-choc p-4 text-cream shadow-lg md:p-6">
            <div className="mx-auto flex max-w-5xl flex-col gap-4 md:flex-row md:items-end">
                <div className="flex-1">
                    <p className="font-serif text-lg">{t('Cookies')}</p>
                    <p className="mt-1 text-sm text-sand">
                        {t(
                            'Wij gebruiken noodzakelijke cookies voor de site. Analytics en marketing laden alleen na uw toestemming.',
                        )}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-4 text-sm">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked disabled />
                            {t('Noodzakelijk')}
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={form.data.analytics}
                                onChange={(event) =>
                                    form.setData(
                                        'analytics',
                                        event.target.checked,
                                    )
                                }
                            />
                            {t('Analytics')}
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={form.data.marketing}
                                onChange={(event) =>
                                    form.setData(
                                        'marketing',
                                        event.target.checked,
                                    )
                                }
                            />
                            {t('Marketing')}
                        </label>
                    </div>
                </div>
                <MaisonButton
                    type="button"
                    variant="gold"
                    disabled={form.processing}
                    onClick={() => form.post(cookieConsentStore.url())}
                >
                    {t('Voorkeuren opslaan')}
                </MaisonButton>
            </div>
        </div>
    );
}
