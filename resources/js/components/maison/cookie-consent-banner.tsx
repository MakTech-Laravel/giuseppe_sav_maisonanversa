import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { store as cookieConsentStore } from '@/routes/cookie-consent';

export const COOKIE_SETTINGS_EVENT = 'maison:open-cookie-settings';

function parseConsent(raw: string | null | undefined): {
    necessary: boolean;
    analytics: boolean;
} | null {
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as {
            necessary?: boolean;
            analytics?: boolean;
        };

        return {
            necessary: true,
            analytics: Boolean(parsed.analytics),
        };
    } catch {
        return null;
    }
}

export function CookieConsentBanner() {
    const { t } = useTranslation();
    const { cookieConsent } = usePage<{ cookieConsent?: string | null }>()
        .props;
    const saved = parseConsent(cookieConsent);
    const [forceOpen, setForceOpen] = useState(false);
    const form = useForm({
        necessary: true,
        analytics: saved?.analytics ?? false,
    });

    useEffect(() => {
        const open = () => {
            const current = parseConsent(cookieConsent);
            form.setData('analytics', current?.analytics ?? false);
            setForceOpen(true);
        };

        window.addEventListener(COOKIE_SETTINGS_EVENT, open);

        return () => window.removeEventListener(COOKIE_SETTINGS_EVENT, open);
        // form.setData is stable enough for this reopen handler; re-bind when consent cookie changes.
        // eslint-disable-next-line react-hooks/exhaustive-deps -- avoid re-subscribing on every form render
    }, [cookieConsent]);

    if (cookieConsent && !forceOpen) {
        return null;
    }

    return (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-gold/30 bg-choc p-4 text-cream shadow-lg md:p-6">
            <div className="mx-auto flex max-w-5xl flex-col gap-4 md:flex-row md:items-end">
                <div className="flex-1">
                    <p className="font-serif text-lg">{t('Cookies')}</p>
                    <p className="mt-1 text-sm text-sand">
                        {t(
                            'We use necessary cookies for the site. Analytics load only after your consent.',
                        )}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-4 text-sm">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked disabled />
                            {t('Necessary')}
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
                    </div>
                </div>
                <MaisonButton
                    type="button"
                    variant="gold"
                    disabled={form.processing}
                    onClick={() =>
                        form.post(cookieConsentStore.url(), {
                            onSuccess: () => setForceOpen(false),
                        })
                    }
                >
                    {t('Save preferences')}
                </MaisonButton>
            </div>
        </div>
    );
}

export function openCookieSettings(): void {
    window.dispatchEvent(new Event(COOKIE_SETTINGS_EVENT));
}
