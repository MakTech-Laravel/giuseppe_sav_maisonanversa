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
            <Head title={t('Retourneren')} />
            <PageHero
                eyebrow={t('Retour')}
                title={t('Wat wil je retourneren?')}
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
                                    {t('Artikelen')}
                                </p>
                                <p className="mt-3 font-serif text-lg text-choc">
                                    {order.label}
                                </p>
                                <p className="mt-2 font-serif text-sm text-[#8A7363] italic">
                                    {t(
                                        'Stuur alles mee wat erbij hoorde: doos, Heritage Passport, kaarten en accessoires.',
                                    )}
                                </p>
                            </div>

                            <div className="border border-choc/15 bg-white px-6 py-6">
                                <p className="font-sans text-[10px] tracking-[0.2em] text-[#8A7363] uppercase">
                                    {t('Reden (optioneel)')}
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
                                    className="mt-3 w-full border border-choc/20 bg-cream px-3 py-3 font-sans text-sm text-choc"
                                />
                                {damaged ? (
                                    <div className="mt-4 space-y-3">
                                        <p className="font-sans text-sm text-[#5C4A3D]">
                                            {t(
                                                'We nemen contact met je op over de retourzending op onze kosten.',
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
                                    {t('Samenvatting')}
                                </p>
                                <p className="mt-3 text-choc">
                                    {t('Verwacht terugbetalingsbedrag')}: €
                                    {expectedRefund}
                                </p>
                                <p className="mt-2 whitespace-pre-line text-[#5C4A3D]">
                                    {returnAddress}
                                </p>
                                <p className="mt-3 font-sans text-sm text-[#5C4A3D]">
                                    {damaged
                                        ? t(
                                              'We nemen contact met je op over de retourzending op onze kosten.',
                                          )
                                        : t(
                                              'De kosten van de retourzending zijn voor jou. We raden een verzekerde zending met tracking aan.',
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
                                            'Ik bevestig dat ik deze artikelen wil retourneren.',
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
                                {t('Retour bevestigen')}
                            </button>

                            <p className="font-sans text-xs text-[#8A7363]">
                                <a
                                    href={`/${locale}/shipping`}
                                    className="underline"
                                >
                                    {t('Verzend- en retourbeleid')}
                                </a>
                            </p>
                        </>
                    )}
                </Form>
            </section>
        </>
    );
}
