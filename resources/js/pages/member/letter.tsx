import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { updateLetter } from '@/actions/App/Http/Controllers/Member/DashboardController';
import {
    MemberPageHeader,
    MemberPanel,
    MemberStatusPill,
} from '@/components/member/member-ui';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

type Preferences = {
    heritageLetter: boolean;
    productUpdates: boolean;
    events: boolean;
};

export default function MemberLetter({
    preferences,
    status,
}: {
    preferences: Preferences;
    status: string | null;
}) {
    const { t } = useTranslation();
    const form = useForm(updateLetter(wayfinderLocale()), preferences);

    function onSubmit(event: FormEvent) {
        event.preventDefault();
        form.submit();
    }

    const options = [
        ['heritageLetter', t('Heritage Letter')] as const,
        ['productUpdates', t('Productupdates')] as const,
        ['events', t('Sessies & events')] as const,
    ];

    const isSubscribed = status === 'subscribed';

    return (
        <>
            <Head title={t('Heritage Letter')} />
            <MemberPageHeader
                eyebrow={t('Correspondentie')}
                title={t('Heritage Letter-voorkeuren')}
                description={t(
                    'Kies welke berichten u ontvangt. Wijzigingen worden meteen bewaard.',
                )}
            />

            <MemberPanel className="max-w-xl">
                <div className="mb-6 flex items-center gap-3">
                    <MemberStatusPill tone={isSubscribed ? 'success' : 'warn'}>
                        {isSubscribed
                            ? t('U staat op de lijst.')
                            : t('U staat niet op de lijst.')}
                    </MemberStatusPill>
                </div>

                <form onSubmit={onSubmit} className="space-y-5">
                    {options.map(([key, label]) => (
                        <label
                            key={key}
                            className="flex cursor-pointer items-center gap-3"
                        >
                            <input
                                type="checkbox"
                                checked={form.data[key]}
                                onChange={(event) =>
                                    form.setData(key, event.target.checked)
                                }
                                className="size-4 accent-gold"
                            />
                            <span className="font-sans text-[12px] tracking-[0.14em] text-cream uppercase">
                                {label}
                            </span>
                        </label>
                    ))}

                    <Button type="submit" className="mt-4" disabled={form.processing}>
                        {t('Voorkeuren opslaan')}
                    </Button>

                    {form.recentlySuccessful && (
                        <p className="font-sans text-[11px] tracking-[0.12em] text-gold2 uppercase">
                            {t('Voorkeuren opgeslagen.')}
                        </p>
                    )}
                </form>
            </MemberPanel>
        </>
    );
}
