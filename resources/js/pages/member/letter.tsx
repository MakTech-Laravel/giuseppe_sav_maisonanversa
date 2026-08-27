import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { updateLetter } from '@/actions/App/Http/Controllers/Member/DashboardController';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
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

    return (
        <>
            <Head title={t('Heritage Letter')} />
            <MemberPageHeader
                eyebrow={t('De Heritage Letter')}
                title={t('Heritage Letter')}
                description={t(
                    'De Heritage Letter is de nieuwsbrief van het huis. Kies of u die ontvangt, en of we ook product- en eventberichten sturen.',
                )}
            />

            <MemberPanel className="max-w-xl">
                <div className="mb-6 flex items-center gap-3">
                    <MemberStatusPill
                        tone={form.data.heritageLetter ? 'success' : 'warn'}
                    >
                        {form.data.heritageLetter
                            ? t('U ontvangt de Heritage Letter.')
                            : t('U ontvangt de Heritage Letter nog niet.')}
                    </MemberStatusPill>
                </div>

                <form onSubmit={onSubmit} className="space-y-8">
                    <label className="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            checked={form.data.heritageLetter}
                            onChange={(event) =>
                                form.setData(
                                    'heritageLetter',
                                    event.target.checked,
                                )
                            }
                            className="mt-0.5 size-4 accent-gold"
                        />
                        <span className="space-y-1">
                            <span className="block font-sans text-[12px] tracking-[0.14em] text-cream uppercase">
                                {t('Heritage Letter')}
                            </span>
                            <span className="block text-[13px] leading-relaxed text-sand">
                                {t(
                                    'Als eerste verhalen, nieuws en exclusieve uitnodigingen ontvangen van Maison Anversa.',
                                )}
                            </span>
                        </span>
                    </label>

                    <div>
                        <MemberSectionTitle title={t('Extra berichten')} />
                        <div className="space-y-5">
                            {(
                                [
                                    ['productUpdates', t('Productupdates')],
                                    ['events', t('Sessies & events')],
                                ] as const
                            ).map(([key, label]) => (
                                <label
                                    key={key}
                                    className="flex cursor-pointer items-center gap-3"
                                >
                                    <input
                                        type="checkbox"
                                        checked={form.data[key]}
                                        onChange={(event) =>
                                            form.setData(
                                                key,
                                                event.target.checked,
                                            )
                                        }
                                        className="size-4 accent-gold"
                                    />
                                    <span className="font-sans text-[12px] tracking-[0.14em] text-cream uppercase">
                                        {label}
                                    </span>
                                </label>
                            ))}
                        </div>
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        {t('Opslaan')}
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
