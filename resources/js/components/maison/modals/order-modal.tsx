import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
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

type Step = 1 | 2;

/**
 * Checkout flow: contact + shipping details, then payment summary.
 * Limited editions expect an edition selected upstream in the picker.
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
    const selectedEditionLabel =
        product?.editionLabel ??
        (product?.editionNumber != null
            ? t('Nr. {{number}}', {
                  number: String(product.editionNumber).padStart(3, '0'),
              })
            : null);
    const [step, setStep] = useState<Step>(1);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [shippingLine1, setShippingLine1] = useState('');
    const [shippingLine2, setShippingLine2] = useState('');
    const [shippingCity, setShippingCity] = useState('');
    const [shippingPostalCode, setShippingPostalCode] = useState('');
    const [shippingCountry, setShippingCountry] = useState('BE');
    const [giftWrap, setGiftWrap] = useState(false);
    const [giftMessage, setGiftMessage] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const steps = useMemo(
        () =>
            [
                { n: 1 as Step, label: t('Gegevens') },
                { n: 2 as Step, label: t('Betaling') },
            ] as const,
        [t],
    );

    function onStepOne(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        setError(null);

        if (
            !name.trim() ||
            !email.trim() ||
            !shippingLine1.trim() ||
            !shippingCity.trim() ||
            !shippingPostalCode.trim() ||
            !shippingCountry.trim()
        ) {
            setError(t('Vul a.u.b. uw contact- en verzendgegevens in.'));

            return;
        }

        setStep(2);
    }

    function onPay(): void {
        if (processing) {
            return;
        }

        if (isLimitedEdition && !product?.editionPieceId) {
            setError(t('Kies a.u.b. een beschikbaar editienummer.'));

            return;
        }

        setError(null);
        setProcessing(true);

        router.post(
            checkoutStore.url(locale),
            {
                ...(productId !== null ? { product_id: productId } : {}),
                ...(product?.editionPieceId
                    ? { edition_piece_id: product.editionPieceId }
                    : {}),
                name: name.trim(),
                email: email.trim(),
                phone: phone.trim() || null,
                shipping_line1: shippingLine1.trim(),
                shipping_line2: shippingLine2.trim() || null,
                shipping_city: shippingCity.trim(),
                shipping_postal_code: shippingPostalCode.trim(),
                shipping_country: shippingCountry.trim().toUpperCase(),
                gift_wrap: giftWrap,
                gift_message: giftMessage.trim() || null,
            },
            {
                onError: (errors) => {
                    const message =
                        errors.checkout ||
                        errors.edition_piece_id ||
                        errors.shipping_line1 ||
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
            label={t(isLimitedEdition ? 'Reserveer Uw Nummer' : 'Bestel nu')}
            onClose={onClose}
            panelClassName="max-w-[640px]"
        >
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {t(isLimitedEdition ? 'Reserveer Uw Nummer' : 'Bestel nu')}
            </h2>
            <span className="mb-4 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {`${productName} · ${priceLabel}`}
                {selectedEditionLabel ? ` · ${selectedEditionLabel}` : ''}
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
                    <input
                        id="ord-name"
                        type="text"
                        required
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        placeholder={t('Volledige naam')}
                        className={modalInputClassName}
                    />
                    <input
                        id="ord-email"
                        type="email"
                        required
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        placeholder={t('E-mailadres')}
                        className={modalInputClassName}
                    />
                    <input
                        id="ord-phone"
                        type="tel"
                        value={phone}
                        onChange={(event) => setPhone(event.target.value)}
                        placeholder={t('Telefoonnummer (optioneel)')}
                        className={modalInputClassName}
                    />

                    <p className="mt-1 font-sans text-[10px] tracking-[0.16em] text-choc3 uppercase">
                        {t('Verzendadres')}
                    </p>
                    <input
                        type="text"
                        required
                        value={shippingLine1}
                        onChange={(event) => setShippingLine1(event.target.value)}
                        placeholder={t('Straat en huisnummer')}
                        className={modalInputClassName}
                    />
                    <input
                        type="text"
                        value={shippingLine2}
                        onChange={(event) => setShippingLine2(event.target.value)}
                        placeholder={t('Adresregel 2 (optioneel)')}
                        className={modalInputClassName}
                    />
                    <div className="grid grid-cols-2 gap-3">
                        <input
                            type="text"
                            required
                            value={shippingPostalCode}
                            onChange={(event) =>
                                setShippingPostalCode(event.target.value)
                            }
                            placeholder={t('Postcode')}
                            className={modalInputClassName}
                        />
                        <input
                            type="text"
                            required
                            value={shippingCity}
                            onChange={(event) => setShippingCity(event.target.value)}
                            placeholder={t('Stad')}
                            className={modalInputClassName}
                        />
                    </div>
                    <input
                        type="text"
                        required
                        maxLength={2}
                        value={shippingCountry}
                        onChange={(event) =>
                            setShippingCountry(
                                event.target.value.toUpperCase().slice(0, 2),
                            )
                        }
                        placeholder={t('Land (ISO, bv. BE)')}
                        className={modalInputClassName}
                    />

                    {error && (
                        <p role="alert" className="text-[13px] text-choc3">
                            {error}
                        </p>
                    )}

                    <MaisonButton type="submit" variant="filled" block>
                        {t('Volgende — Betaling')}
                    </MaisonButton>
                </form>
            ) : (
                <div>
                    <OrderSummary
                        product={product}
                        name={name}
                        email={email}
                        phone={phone}
                        shippingLine1={shippingLine1}
                        shippingLine2={shippingLine2}
                        shippingCity={shippingCity}
                        shippingPostalCode={shippingPostalCode}
                        shippingCountry={shippingCountry}
                        giftWrap={giftWrap}
                        giftMessage={giftMessage}
                        editionLabel={selectedEditionLabel}
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
                                    'min-h-[72px] w-full max-w-full resize-y break-words font-sans text-[13px]',
                                )}
                            />
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
                            onClick={() => setStep(1)}
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
    shippingLine1,
    shippingLine2,
    shippingCity,
    shippingPostalCode,
    shippingCountry,
    giftWrap,
    giftMessage,
    editionLabel,
}: {
    product?: OrderProductContext;
    name: string;
    email: string;
    phone: string;
    shippingLine1: string;
    shippingLine2: string;
    shippingCity: string;
    shippingPostalCode: string;
    shippingCountry: string;
    giftWrap: boolean;
    giftMessage: string;
    editionLabel: string | null;
}) {
    const { t } = useTranslation();
    const { priceLabel, productName, productType } =
        useCheckoutDisplay(product);
    const isLimitedEdition = productType !== 'simple';
    const trimmedPhone = phone.trim();
    const trimmedMessage = giftMessage.trim();
    const shippingValue = [
        shippingLine1.trim(),
        shippingLine2.trim(),
        `${shippingPostalCode.trim()} ${shippingCity.trim()}`.trim(),
        shippingCountry.trim().toUpperCase(),
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <div className="mb-4 overflow-hidden rounded border border-gold/22 bg-black/3 p-4.5">
            <SummaryRow label={t('Product')} value={productName} />
            <SummaryRow label={t('Houder')} value={name.trim()} />
            {isLimitedEdition && editionLabel && (
                <SummaryRow label={t('Editienummer')} value={editionLabel} />
            )}
            <SummaryRow label={t('E-mail')} value={email.trim()} />
            {trimmedPhone && (
                <SummaryRow label={t('Telefoon')} value={trimmedPhone} />
            )}
            <SummaryRow label={t('Verzendadres')} value={shippingValue} />
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
            <span className="shrink-0">{label}</span>
            <strong
                className={cn(
                    'min-w-0 flex-1 break-words text-right font-medium text-choc',
                    total && 'font-serif text-lg text-gold',
                )}
            >
                {value}
            </strong>
        </div>
    );
}
