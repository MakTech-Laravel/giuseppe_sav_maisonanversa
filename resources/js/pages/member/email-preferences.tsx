import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import {
    updateEmailPreferences,
    updateSessionEmails,
} from '@/actions/App/Http/Controllers/Member/DashboardController';
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

export default function MemberEmailPreferences({
    preferences,
    sessionEmails,
}: {
    preferences: Preferences;
    status: string | null;
    sessionEmails: boolean;
}) {
    const { t } = useTranslation();
    const form = useForm(
        updateEmailPreferences(wayfinderLocale()),
        preferences,
    );
    const sessionForm = useForm(updateSessionEmails(wayfinderLocale()), {
        session_emails: sessionEmails,
    });

    function onSessionSubmit(event: FormEvent) {
        event.preventDefault();
        sessionForm.submit({ preserveScroll: true });
    }

    function onSubmit(event: FormEvent) {
        event.preventDefault();
        form.submit();
    }

    return (
        <>
            <Head title={t('Email preferences')} />
            <MemberPageHeader
                eyebrow={t('The Heritage Letter')}
                title={t('Email preferences')}
                description={t(
                    'The Heritage Letter is the house newsletter. Choose whether you receive it, and whether we also send product and event notes.',
                )}
            />

            <MemberPanel className="max-w-xl">
                <div className="mb-6 flex items-center gap-3">
                    <MemberStatusPill
                        tone={form.data.heritageLetter ? 'success' : 'warn'}
                    >
                        {form.data.heritageLetter
                            ? t('You receive the Heritage Letter.')
                            : t('You are not receiving the Heritage Letter yet.')}
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
                                    'Be the first to receive stories, news and exclusive invitations from Maison Anversa.',
                                )}
                            </span>
                        </span>
                    </label>

                    <div>
                        <MemberSectionTitle title={t('Extra messages')} />
                        <div className="space-y-5">
                            {(
                                [
                                    ['productUpdates', t('Product updates')],
                                    ['events', t('Sessions & events')],
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
                        {t('Save')}
                    </Button>

                    {form.recentlySuccessful && (
                        <p className="font-sans text-[11px] tracking-[0.12em] text-gold2 uppercase">
                            {t('Preferences saved.')}
                        </p>
                    )}
                </form>
            </MemberPanel>

            <MemberPanel className="mt-8 max-w-xl">
                <MemberSectionTitle
                    title={t('Session emails')}
                    description={t(
                        'In-app notifications always stay on. Here you only choose whether you also receive them by email.',
                    )}
                />

                <form onSubmit={onSessionSubmit} className="space-y-6">
                    <label className="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            checked={sessionForm.data.session_emails}
                            onChange={(event) =>
                                sessionForm.setData(
                                    'session_emails',
                                    event.target.checked,
                                )
                            }
                            className="mt-0.5 size-4 accent-gold"
                        />
                        <span className="space-y-1">
                            <span className="block font-sans text-[12px] tracking-[0.14em] text-cream uppercase">
                                {t('Emails about my sessions')}
                            </span>
                            <span className="block text-[13px] leading-relaxed text-sand">
                                {t(
                                    'New players, confirmations, full and cancelled sessions, opened-up spots and the reminder the day before.',
                                )}
                            </span>
                        </span>
                    </label>

                    <Button type="submit" disabled={sessionForm.processing}>
                        {t('Save')}
                    </Button>

                    {sessionForm.recentlySuccessful && (
                        <p className="font-sans text-[11px] tracking-[0.12em] text-gold2 uppercase">
                            {t('Preferences saved.')}
                        </p>
                    )}
                </form>
            </MemberPanel>
        </>
    );
}
