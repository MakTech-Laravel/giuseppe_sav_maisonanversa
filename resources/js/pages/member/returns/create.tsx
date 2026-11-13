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
            <Head title={t('Returns')} />
            <div className="mb-6">
                <Link
                    href={`/${locale}/member/orders/${order.id}`}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline hover:text-cream"
                >
                    ← {t('Back to order')}
                </Link>
            </div>

            <MemberPageHeader
                eyebrow={t('Return')}
                title={t('What would you like to return?')}
                description={order.reference}
            />

            <ol className="mb-6 flex flex-wrap gap-2">
                {[
                    t('Items'),
                    t('Reason'),
                    t('Confirm'),
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
                                <MemberSectionTitle title={t('Items')} />
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
                                                'Include everything that came with it: box, Heritage Passport, cards and accessories.',
                                            )}
                                        </span>
                                    </span>
                                </label>
                            </MemberPanel>
                        </div>

                        <div className={cn(stepKey !== 'reason' && 'hidden')}>
                            <MemberPanel>
                                <MemberSectionTitle
                                    title={t('Reason (optional)')}
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
                                        {t('Reason (optional)')}
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
                                    placeholder={t('Optional comment')}
                                    className="mt-3 w-full border border-gold/25 bg-choc px-3 py-3 font-sans text-sm text-cream"
                                />
                                {damaged ? (
                                    <div className="mt-4 space-y-3">
                                        <p className="font-sans text-sm text-sand">
                                            {t(
                                                'We will contact you about return shipping at our expense.',
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
                                <MemberSectionTitle title={t('Summary')} />
                                <p className="text-cream">
                                    {t('Expected refund amount')}: €
                                    {expectedRefund}
                                </p>
                                <p className="mt-2 text-sand">{returnAddress}</p>
                                <p className="mt-3 font-sans text-sm text-sand">
                                    {damaged
                                        ? t(
                                              'We will contact you about return shipping at our expense.',
                                          )
                                        : t(
                                              'Return shipping costs are yours. We recommend insured shipping with tracking.',
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
                                            'I confirm that I want to return these items.',
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
                                    {t('Back')}
                                </button>
                            )}
                            {step < STEPS.length - 1 ? (
                                <button
                                    type="button"
                                    onClick={() => setStep(step + 1)}
                                    className="inline-flex min-h-11 items-center justify-center border border-cream bg-cream px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase"
                                >
                                    {t('Continue')}
                                </button>
                            ) : (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-11 items-center justify-center border border-cream bg-cream px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {t('Confirm return')}
                                </button>
                            )}
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
