import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { store } from '@/actions/App/Http/Controllers/Member/FoundingCircleClaimController';
import {
    MemberEmptyState,
    MemberPageHeader,
    MemberPanel,
} from '@/components/member/member-ui';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

type ClaimRow = {
    id: string;
    edition_number: string;
    racket_label: string;
    product_name: string | null;
    status: string;
    status_label: string;
    source: string;
    source_label: string;
    admin_note: string | null;
    created_at: string | null;
    reviewed_at: string | null;
};

export default function MemberRacketRegistration({
    claims,
    canSubmit,
}: {
    claims: ClaimRow[];
    canSubmit: boolean;
}) {
    const { t } = useTranslation();
    const form = useForm(store(wayfinderLocale()), {
        serial: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            onSuccess: () => form.reset('serial'),
        });
    }

    return (
        <>
            <Head title={t('Racketregistratie')} />
            <MemberPageHeader
                eyebrow={t('Founding Circle')}
                title={t('Racketregistratie')}
                description={t(
                    'Registreer uw limited racket (001/100–100/100). Na goedkeuring activeert het team uw Founding Circle-status en Heritage Passport.',
                )}
            />

            {canSubmit ? (
                <MemberPanel className="mb-8">
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label
                                htmlFor="serial"
                                className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase"
                            >
                                {t('Serienummer')}
                            </label>
                            <input
                                id="serial"
                                type="text"
                                value={form.data.serial}
                                onChange={(event) =>
                                    form.setData('serial', event.target.value)
                                }
                                placeholder={t('bijv. 027/100')}
                                className="mt-2 w-full border border-gold/35 bg-choc3 px-4 py-3 font-sans text-[14px] text-cream placeholder:text-sand/50 focus:border-gold focus:outline-none"
                                autoComplete="off"
                            />
                            {form.errors.serial && (
                                <p className="mt-2 text-[13px] text-red-300">
                                    {form.errors.serial}
                                </p>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="border border-gold bg-gold px-5 py-2.5 font-sans text-[11px] tracking-[0.16em] text-choc uppercase transition-opacity disabled:opacity-50"
                        >
                            {t('Registratie indienen')}
                        </button>
                    </form>
                </MemberPanel>
            ) : (
                <MemberPanel className="mb-8">
                    <p className="text-[15px] text-sand">
                        {t(
                            'U heeft al een registratie in afwachting. We controleren uw serienummer.',
                        )}
                    </p>
                </MemberPanel>
            )}

            {claims.length === 0 ? (
                <MemberEmptyState
                    title={t('Nog geen registraties')}
                    description={t(
                        'Zodra u een serienummer indient — of een Founding Edition koopt — verschijnt de status hier.',
                    )}
                />
            ) : (
                <div className="space-y-4">
                    <h2 className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                        {t('Uw registraties')}
                    </h2>
                    {claims.map((claim) => (
                        <MemberPanel key={claim.id}>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                                        {claim.product_name ??
                                            t('Heritage No.001')}
                                    </p>
                                    <p className="mt-2 font-serif text-[28px] text-cream">
                                        {t('Racket {{label}}', {
                                            label: claim.racket_label,
                                        })}
                                    </p>
                                    <p className="mt-2 text-[13px] text-sand">
                                        {claim.source_label}
                                        {claim.created_at
                                            ? ` · ${claim.created_at}`
                                            : ''}
                                    </p>
                                </div>
                                <p className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase">
                                    {t(claim.status_label)}
                                </p>
                            </div>
                            {claim.admin_note && (
                                <p className="mt-4 text-[14px] text-sand">
                                    {claim.admin_note}
                                </p>
                            )}
                        </MemberPanel>
                    ))}
                </div>
            )}
        </>
    );
}
