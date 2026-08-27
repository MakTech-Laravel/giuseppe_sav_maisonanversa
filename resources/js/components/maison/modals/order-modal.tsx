import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type {FormEvent} from 'react';
import { useTranslation } from 'react-i18next';
import { store as checkoutStore } from '@/actions/App/Http/Controllers/Maison/CheckoutController';
import { MaisonLink } from '@/components/maison/maison-link';
import {
    MaisonModal,
    modalInputClassName,
    modalNoteClassName,
} from '@/components/maison/modals/maison-modal';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { useCheckoutDisplay } from '@/hooks/use-checkout-display';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';

type OrderModalProps = {
    onClose: () => void;
    /** Per-product checkout context; omitted falls back to the founding SKU. */
    product?: OrderProductContext;
};

type Step = 1 | 2 | 3;

/**
 * The reservation flow: details, then (for numbered limited editions only) a
 * preferred-number/monogram step, then payment summary. Simple-type products
 * skip the monogram step entirely. Submits to Cashier Checkout (EUR).
 */
export function OrderModal({ onClose, product }: OrderModalProps) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const {
        productId,
        priceLabel,
        productName,
        productType,
        deliveryLabel,
        shippingEuIncluded,
        shippingEstimateMin,
        shippingEstimateMax,
    } = useCheckoutDisplay(product);
    const isLimitedEdition = productType !== 'simple';
    const [step, setStep] = useState<Step>(1);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [monogram, setMonogram] = useState('');
    const [giftWrap, setGiftWrap] = useState(false);
    const [giftMessage, setGiftMessage] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const steps = useMemo(
        () =>
            (isLimitedEdition
                ? [
                      { n: 1, label: t('Gegevens') },
                      { n: 2, label: t('Monogram') },
                      { n: 3, label: t('Betaling') },
                  ]
                : [
                      { n: 1, label: t('Gegevens') },
                      { n: 3, label: t('Betaling') },
                  ]) as { n: Step; label: string }[],
        [t, isLimitedEdition],
    );

    function onStepOne(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        setError(null);

        if (!name.trim() || !email.trim()) {
            setError(t('Vul a.u.b. uw naam en e-mailadres in.'));

            return;
        }

        setStep(isLimitedEdition ? 2 : 3);
    }

    function onPay(): void {
        if (processing) {
            return;
        }

        setError(null);
        setProcessing(true);

        router.post(
            checkoutStore.url(locale),
            {
                ...(productId !== null ? { product_id: productId } : {}),
                name: name.trim(),
                email: email.trim(),
                phone: phone.trim() || null,
                monogram: monogram.trim().toUpperCase() || null,
                gift_wrap: giftWrap,
                gift_message: giftMessage.trim() || null,
            },
            {
                onError: (errors) => {
                    const message =
                        errors.checkout ||
                        errors.email ||
                        errors.name ||
                        Object.values(errors)[0];

                    setError(
                        typeof message === 'string'
                            ? message
                            : t(
                                  'Betaling kon niet worden gestart. Probeer opnieuw.',
                              ),
                    );
                    setProcessing(false);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    }

    return (
        <MaisonModal
            label={
                isLimitedEdition ? 'Reserveer Uw Nummer' : 'Bestel nu'
            }
            onClose={onClose}
            panelClassName="max-w-[480px]"
        >
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {t(isLimitedEdition ? 'Reserveer Uw Nummer' : 'Bestel nu')}
            </h2>
            <span className="mb-4 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {`${productName} · ${priceLabel}`}
            </span>

            <div className="mb-5 flex gap-2">
                {steps.map(({ n, label }, index) => (
                    <div
                        key={n}
                        className={cn(
                            'flex-1 border-b pb-2.5 text-center font-sans text-[9px] tracking-[0.18em] text-stone uppercase',
                            step === n && 'border-gold text-choc',
                            step > n && 'border-gold/25',
                            step < n && 'border-gold/25',
                        )}
                    >
                        <span
                            className={cn(
                                'mb-1 block font-serif text-lg text-gold',
                                step > n && 'opacity-55',
                            )}
                        >
                            {index + 1}
                        </span>
                        {label}
                    </div>
                ))}
            </div>

            {step === 1 ? (
                <form onSubmit={onStepOne} className="flex flex-col gap-3.5">
                    <label className="sr-only" htmlFor="ord-name">
                        {t('Volledige naam')}
                    </label>
                    <input
                        id="ord-name"
                        type="text"
                        required
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        placeholder={t('Volledige naam')}
                        className={modalInputClassName}
                    />

                    <label className="sr-only" htmlFor="ord-email">
                        {t('E-mailadres')}
                    </label>
                    <input
                        id="ord-email"
                        type="email"
                        required
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        placeholder={t('E-mailadres')}
                        className={modalInputClassName}
                    />

                    <label className="sr-only" htmlFor="ord-phone">
                        {t('Telefoonnummer (optioneel)')}
                    </label>
                    <input
                        id="ord-phone"
                        type="tel"
                        value={phone}
                        onChange={(event) => setPhone(event.target.value)}
                        placeholder={t('Telefoonnummer (optioneel)')}
                        className={modalInputClassName}
                    />

                    {error && (
                        <p role="alert" className="text-[13px] text-choc3">
                            {error}
                        </p>
                    )}

                    <MaisonButton type="submit" variant="filled" block>
                        {isLimitedEdition
                            ? t('Volgende — Monogram')
                            : t('Volgende — Betaling')}
                    </MaisonButton>
                </form>
            ) : step === 2 ? (
                <div>
                    <p className="mb-3.5 text-[13px] leading-[1.6] text-choc3">
                        {t(
                            'Uw editienummer wordt automatisch toegewezen na succesvolle betaling. No. 001 blijft in het archief.',
                        )}
                    </p>

                    <div className="mb-4">
                        <label
                            htmlFor="ord-mono"
                            className="mb-2 block font-sans text-[10px] tracking-[0.18em] text-choc3 uppercase"
                        >
                            {t(
                                'Monogram in het leder (optioneel, max 3 letters)',
                            )}
                        </label>
                        <input
                            id="ord-mono"
                            type="text"
                            maxLength={3}
                            value={monogram}
                            onChange={(event) =>
                                setMonogram(
                                    event.target.value
                                        .replace(/[^a-zA-Z]/g, '')
                                        .toUpperCase(),
                                )
                            }
                            placeholder="MA"
                            className={modalInputClassName}
                        />
                    </div>

                    <div className="flex items-center gap-2.5">
                        <MaisonButton
                            type="button"
                            variant="outlineChoc"
                            onClick={() => setStep(1)}
                        >
                            {t('← Terug')}
                        </MaisonButton>
                        <MaisonButton
                            type="button"
                            variant="filled"
                            block
                            onClick={() => setStep(3)}
                        >
                            {t('Volgende — Betaling')}
                        </MaisonButton>
                    </div>
                </div>
            ) : (
                <div>
                    <OrderSummary
                        product={product}
                        name={name}
                        email={email}
                        phone={phone}
                        monogram={monogram}
                        giftWrap={giftWrap}
                        giftMessage={giftMessage}
                    />

                    <label className="mb-4 flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            checked={giftWrap}
                            onChange={(event) =>
                                setGiftWrap(event.target.checked)
                            }
                            className="mt-1"
                        />
                        <span className="text-[13px] leading-[1.5] text-choc3">
                            {t('Cadeauverpakking toevoegen')}
                        </span>
                    </label>

                    {giftWrap && (
                        <div className="mb-4">
                            <label
                                htmlFor="ord-gift"
                                className="mb-2 block font-sans text-[10px] tracking-[0.18em] text-choc3 uppercase"
                            >
                                {t('Cadeauboodschap (optioneel)')}
                            </label>
                            <textarea
                                id="ord-gift"
                                value={giftMessage}
                                onChange={(event) =>
                                    setGiftMessage(event.target.value)
                                }
                                className={cn(
                                    modalInputClassName,
                                    'min-h-[72px] resize-y font-sans text-[13px]',
                                )}
                            />
                            <p className="mt-2.5 font-sans text-[9px] tracking-[0.12em] text-choc3 uppercase">
                                {t(
                                    'Luxe cadeauverpakking · handgeschreven kaart · geen prijs op de pakbon',
                                )}
                            </p>
                        </div>
                    )}

                    {error && (
                        <p role="alert" className="mb-3 text-[13px] text-choc3">
                            {error}
                        </p>
                    )}

                    <div className="flex items-center gap-2.5">
                        <MaisonButton
                            type="button"
                            variant="outlineChoc"
                            onClick={() => setStep(isLimitedEdition ? 2 : 1)}
                            disabled={processing}
                        >
                            {t('← Terug')}
                        </MaisonButton>
                        <MaisonButton
                            type="button"
                            variant="filled"
                            block
                            disabled={processing}
                            onClick={onPay}
                        >
                            {processing
                                ? t('Bezig…')
                                : `${t('Ga naar betaling —')} ${priceLabel}`}
                        </MaisonButton>
                    </div>

                    <p className={cn(modalNoteClassName, 'mt-3')}>
                        {t(
                            'U wordt doorgestuurd naar onze beveiligde betaalpagina. Betaling via Stripe.',
                        )}
                    </p>
                    {deliveryLabel ? (
                        <p className={cn(modalNoteClassName, 'mt-2')}>
                            {deliveryLabel}
                        </p>
                    ) : null}
                    <p className={cn(modalNoteClassName, 'mt-2')}>
                        {shippingEuIncluded
                            ? t('Verzending inbegrepen in de EU (indicatie).')
                            : t(
                                  'Verzending later in rekening gebracht — indicatie {{min}}–{{max}} €.',
                                  {
                                      min: shippingEstimateMin,
                                      max: shippingEstimateMax,
                                  },
                              )}
                    </p>
                </div>
            )}

            <p className={modalNoteClassName}>
                {t('Beveiligde betaling via Stripe · SSL-versleuteld ·')}{' '}
                <MaisonLink
                    to="terms"
                    className="underline underline-offset-2 hover:text-gold"
                    onClick={onClose}
                >
                    {t('Voorwaarden')}
                </MaisonLink>
            </p>
        </MaisonModal>
    );
}

function OrderSummary({
    product,
    name,
    email,
    phone,
    monogram,
    giftWrap,
    giftMessage,
}: {
    product?: OrderProductContext;
    name: string;
    email: string;
    phone: string;
    monogram: string;
    giftWrap: boolean;
    giftMessage: string;
}) {
    const { t } = useTranslation();
    const { priceLabel, productName, productType } =
        useCheckoutDisplay(product);
    const isLimitedEdition = productType !== 'simple';
    const mono = monogram.trim().toUpperCase();
    const trimmedPhone = phone.trim();
    const trimmedMessage = giftMessage.trim();

    return (
        <div className="mb-4 rounded border border-gold/22 bg-black/3 p-4.5">
            <SummaryRow label={t('Product')} value={productName} />
            <SummaryRow label={t('Houder')} value={name.trim()} />
            {isLimitedEdition && (
                <SummaryRow
                    label={t('Editienummer')}
                    value={t('Toegewezen na betaling')}
                />
            )}
            {mono && <SummaryRow label={t('Monogram')} value={mono} />}
            <SummaryRow label={t('E-mail')} value={email.trim()} />
            {trimmedPhone && (
                <SummaryRow label={t('Telefoon')} value={trimmedPhone} />
            )}
            {giftWrap && (
                <>
                    <SummaryRow
                        label={t('Cadeauverpakking')}
                        value={t('Ja · geen prijs op pakbon')}
                    />
                    {trimmedMessage && (
                        <SummaryRow
                            label={t('Boodschap')}
                            value={trimmedMessage}
                        />
                    )}
                </>
            )}
            <SummaryRow label={t('Totaal')} value={priceLabel} total />
            {isLimitedEdition && (
                <p className="mt-2.5 font-sans text-[10px] leading-[1.6] tracking-[0.1em] text-choc3 uppercase">
                    {t(
                        'Het editienummer wordt toegewezen na bevestigde betaling. No. 001 blijft in het Maison Anversa-archief.',
                    )}
                </p>
            )}
        </div>
    );
}

function SummaryRow({
    label,
    value,
    total = false,
}: {
    label: string;
    value: string;
    total?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex justify-between gap-4 border-b border-gold/14 py-1.75 font-sans text-xs text-choc3',
                total && 'border-b-0 pt-3',
            )}
        >
            <span>{label}</span>
            <strong
                className={cn(
                    'text-right font-medium text-choc',
                    total && 'font-serif text-lg text-gold',
                )}
            >
                {value}
            </strong>
        </div>
    );
}
