import { Form, Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';

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
    };
    reasons: ReasonOption[];
    returnAddress: string;
    expectedRefund: string;
    storeUrl: string;
};

export default function GuestReturnCreate({
    order,
    reasons,
    returnAddress,
    expectedRefund,
    storeUrl,
}: Props) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [reason, setReason] = useState('');
    const selected = reasons.find((option) => option.value === reason);
    const damaged = Boolean(selected?.maison_pays_shipping);

    return (
        <>
            <Head title={t('Returns')} />
            <PageHero
                eyebrow={t('Return')}
                title={t('What would you like to return?')}
                subtitle={order.reference}
            />

            <section className="bg-cream px-[6vw] py-16 text-choc">
                <Form
                    action={storeUrl}
                    method="post"
                    encType="multipart/form-data"
                    className="mx-auto max-w-2xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="border border-choc/15 bg-white px-6 py-6">
                                <p className="font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                                    {t('Items')}
                                </p>
                                <p className="mt-3 font-serif text-lg text-choc">
                                    {order.label}
                                </p>
                                <p className="mt-2 font-serif text-sm text-[#8A7363] italic">
                                    {t(
                                        'Include everything that came with it: box, Heritage Passport, cards and accessories.',
                                    )}
                                </p>
                            </div>

                            <div className="border border-choc/15 bg-white px-6 py-6">
                                <p className="font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                                    {t('Reason (optional)')}
                                </p>
                                <select
                                    name="reason"
                                    value={reason}
                                    onChange={(event) =>
                                        setReason(event.target.value)
                                    }
                                    className="mt-3 w-full border border-choc/20 bg-cream px-3 py-3 font-sans text-sm text-choc"
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
                                    className="mt-3 w-full border border-choc/20 bg-cream px-3 py-3 font-sans text-sm text-choc"
                                />
                                {damaged ? (
                                    <div className="mt-4 space-y-3">
                                        <p className="font-sans text-sm text-[#5C4A3D]">
                                            {t(
                                                'We will contact you about return shipping at our expense.',
                                            )}
                                        </p>
                                        <input
                                            type="file"
                                            name="photos[]"
                                            accept="image/png,image/jpeg,image/webp"
                                            multiple
                                            className="block w-full font-sans text-sm"
                                        />
                                        {errors.photos ? (
                                            <p className="text-sm text-red-700">
                                                {errors.photos}
                                            </p>
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>

                            <div className="border border-choc/15 bg-white px-6 py-6">
                                <p className="font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                                    {t('Summary')}
                                </p>
                                <p className="mt-3 text-choc">
                                    {t('Expected refund amount')}: €
                                    {expectedRefund}
                                </p>
                                <p className="mt-2 whitespace-pre-line text-[#5C4A3D]">
                                    {returnAddress}
                                </p>
                                <p className="mt-3 font-sans text-sm text-[#5C4A3D]">
                                    {damaged
                                        ? t(
                                              'We will contact you about return shipping at our expense.',
                                          )
                                        : t(
                                              'Return shipping costs are yours. We recommend insured shipping with tracking.',
                                          )}
                                </p>
                                <label className="mt-4 flex items-start gap-3 text-choc">
                                    <input
                                        type="checkbox"
                                        name="confirmed"
                                        value="1"
                                        required
                                        className="mt-1"
                                    />
                                    <span>
                                        {t(
                                            'I confirm that I want to return these items.',
                                        )}
                                    </span>
                                </label>
                                {errors.confirmed ? (
                                    <p className="mt-2 text-sm text-red-700">
                                        {errors.confirmed}
                                    </p>
                                ) : null}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex min-h-11 items-center justify-center border border-choc bg-choc px-6 font-sans text-[10px] tracking-[0.2em] text-cream uppercase disabled:opacity-60"
                            >
                                {t('Confirm return')}
                            </button>

                            <p className="font-sans text-xs text-[#8A7363]">
                                <a
                                    href={`/${locale}/shipping`}
                                    className="underline"
                                >
                                    {t('Shipping & returns policy')}
                                </a>
                            </p>
                        </>
                    )}
                </Form>
            </section>
        </>
    );
}
