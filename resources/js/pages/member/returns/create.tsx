import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
} from '@/components/member/member-ui';
import { cn } from '@/lib/utils';

type ReasonOption = {
    value: string;
    label: string;
    maison_pays_shipping: boolean;
};

type Props = {
    order: {
        id: string;
        reference?: string;
        label: string;
        amount: string;
        items: { name: string; qty: number; price: string }[];
    };
    reasons: ReasonOption[];
    returnAddress: string;
    expectedRefund: string;
};

const STEPS = ['items', 'reason', 'review'] as const;

export default function MemberReturnCreate({
    order,
    reasons,
    returnAddress,
    expectedRefund,
}: Props) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [step, setStep] = useState(0);
    const [reason, setReason] = useState('');
    const selected = reasons.find((option) => option.value === reason);
    const damaged = Boolean(selected?.maison_pays_shipping);
    const stepKey = STEPS[step];

    return (
        <>
            <Head title={t('Retourneren')} />
            <div className="mb-6">
                <Link
                    href={`/${locale}/member/orders/${order.id}`}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline hover:text-cream"
                >
                    ← {t('Terug naar bestelling')}
                </Link>
            </div>

            <MemberPageHeader
                eyebrow={t('Retour')}
                title={t('Wat wil je retourneren?')}
                description={order.reference}
            />

            <ol className="mb-6 flex flex-wrap gap-2">
                {[
                    t('Artikelen'),
                    t('Reden'),
                    t('Bevestigen'),
                ].map((label, index) => (
                    <li
                        key={label}
                        className={cn(
                            'border px-3 py-2 font-sans text-[10px] tracking-[0.16em] uppercase',
                            index === step
                                ? 'border-cream bg-cream text-choc'
                                : 'border-gold/30 text-sand',
                        )}
                    >
                        {index + 1}. {label}
                    </li>
                ))}
            </ol>

            <Form
                action={`/${locale}/member/orders/${order.id}/returns`}
                method="post"
                encType="multipart/form-data"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className={cn(stepKey !== 'items' && 'hidden')}>
                            <MemberPanel>
                                <MemberSectionTitle title={t('Artikelen')} />
                                <label className="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        checked
                                        readOnly
                                        className="mt-1"
                                    />
                                    <span>
                                        <span className="block text-cream">
                                            {order.label}
                                        </span>
                                        <span className="mt-2 block font-serif text-sm text-sand italic">
                                            {t(
                                                'Stuur alles mee wat erbij hoorde: doos, Heritage Passport, kaarten en accessoires.',
                                            )}
                                        </span>
                                    </span>
                                </label>
                            </MemberPanel>
                        </div>

                        <div className={cn(stepKey !== 'reason' && 'hidden')}>
                            <MemberPanel>
                                <MemberSectionTitle
                                    title={t('Reden (optioneel)')}
                                />
                                <select
                                    name="reason"
                                    value={reason}
                                    onChange={(event) =>
                                        setReason(event.target.value)
                                    }
                                    className="w-full border border-gold/25 bg-choc px-3 py-3 font-sans text-sm text-cream"
                                >
                                    <option value="">
                                        {t('Reden (optioneel)')}
                                    </option>
                                    {reasons.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {t(option.label)}
                                        </option>
                                    ))}
                                </select>
                                <textarea
                                    name="comment"
                                    rows={3}
                                    placeholder={t('Optionele toelichting')}
                                    className="mt-3 w-full border border-gold/25 bg-choc px-3 py-3 font-sans text-sm text-cream"
                                />
                                {damaged ? (
                                    <div className="mt-4 space-y-3">
                                        <p className="font-sans text-sm text-sand">
                                            {t(
                                                'We nemen contact met je op over de retourzending op onze kosten.',
                                            )}
                                        </p>
                                        <input
                                            type="file"
                                            name="photos[]"
                                            accept="image/png,image/jpeg,image/webp"
                                            multiple
                                            className="block w-full font-sans text-sm text-cream"
                                        />
                                        {errors.photos ? (
                                            <p className="text-sm text-red-300">
                                                {errors.photos}
                                            </p>
                                        ) : null}
                                    </div>
                                ) : null}
                            </MemberPanel>
                        </div>

                        <div className={cn(stepKey !== 'review' && 'hidden')}>
                            <MemberPanel>
                                <MemberSectionTitle title={t('Samenvatting')} />
                                <p className="text-cream">
                                    {t('Verwacht terugbetalingsbedrag')}: €
                                    {expectedRefund}
                                </p>
                                <p className="mt-2 text-sand">{returnAddress}</p>
                                <p className="mt-3 font-sans text-sm text-sand">
                                    {damaged
                                        ? t(
                                              'We nemen contact met je op over de retourzending op onze kosten.',
                                          )
                                        : t(
                                              'De kosten van de retourzending zijn voor jou. We raden een verzekerde zending met tracking aan.',
                                          )}
                                </p>
                                <label className="mt-4 flex items-start gap-3 text-cream">
                                    <input
                                        type="checkbox"
                                        name="confirmed"
                                        value="1"
                                        required={stepKey === 'review'}
                                        className="mt-1"
                                    />
                                    <span>
                                        {t(
                                            'Ik bevestig dat ik deze artikelen wil retourneren.',
                                        )}
                                    </span>
                                </label>
                                {errors.confirmed ? (
                                    <p className="mt-2 text-sm text-red-300">
                                        {errors.confirmed}
                                    </p>
                                ) : null}
                            </MemberPanel>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {step > 0 && (
                                <button
                                    type="button"
                                    onClick={() => setStep(step - 1)}
                                    className="inline-flex min-h-11 items-center justify-center border border-gold/40 px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase"
                                >
                                    {t('Terug')}
                                </button>
                            )}
                            {step < STEPS.length - 1 ? (
                                <button
                                    type="button"
                                    onClick={() => setStep(step + 1)}
                                    className="inline-flex min-h-11 items-center justify-center border border-cream bg-cream px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase"
                                >
                                    {t('Doorgaan')}
                                </button>
                            ) : (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-11 items-center justify-center border border-cream bg-cream px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {t('Retour bevestigen')}
                                </button>
                            )}
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
