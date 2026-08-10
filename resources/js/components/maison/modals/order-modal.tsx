import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import {
    MaisonModal,
    modalInputClassName,
    modalNoteClassName,
} from '@/components/maison/modals/maison-modal';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { SuccessPanel } from '@/components/maison/ui/success-panel';
import { cn } from '@/lib/utils';

type OrderModalProps = {
    onClose: () => void;
};

type Step = 1 | 2 | 3;

const EDITION_NUMBERS = Array.from({ length: 100 }, (_, index) => index + 1);

/**
 * The three-step reservation flow: details, preferred number (1–100) with an
 * optional monogram, then payment summary. Prototype-mode until Stripe is wired.
 */
export function OrderModal({ onClose }: OrderModalProps) {
    const { t } = useTranslation();
    const [step, setStep] = useState<Step>(1);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [selectedNumber, setSelectedNumber] = useState<number | null>(null);
    const [monogram, setMonogram] = useState('');
    const [giftWrap, setGiftWrap] = useState(false);
    const [giftMessage, setGiftMessage] = useState('');
    const [paid, setPaid] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        setStep(1);
        setName('');
        setEmail('');
        setPhone('');
        setSelectedNumber(null);
        setMonogram('');
        setGiftWrap(false);
        setGiftMessage('');
        setPaid(false);
        setError(null);
    }, []);

    const steps = useMemo(
        () =>
            [
                { n: 1, label: t('Gegevens') },
                { n: 2, label: t('Nummer') },
                { n: 3, label: t('Betaling') },
            ] as const,
        [t],
    );

    function onStepOne(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        setError(null);

        if (!name.trim() || !email.trim()) {
            setError(t('Vul a.u.b. uw naam en e-mailadres in.'));

            return;
        }

        setStep(2);
    }

    function onStepTwo(): void {
        if (selectedNumber === null) {
            return;
        }

        setStep(3);
    }

    function onPay(): void {
        setPaid(true);
    }

    return (
        <MaisonModal
            label="Reserveer Uw Nummer"
            onClose={onClose}
            panelClassName="max-w-[480px]"
        >
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {t('Reserveer Uw Nummer')}
            </h2>
            <span className="mb-4 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {t('Heritage No.001 · Founding Edition · € 249')}
            </span>

            {!paid && (
                <div className="mb-5 flex gap-2">
                    {steps.map(({ n, label }) => (
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
                                {n}
                            </span>
                            {label}
                        </div>
                    ))}
                </div>
            )}

            {paid ? (
                <SuccessPanel
                    title={t('U wordt doorverwezen naar betaling.')}
                    icon="✓"
                >
                    <p>
                        {t(
                            'De betaalpagina opent in een nieuw venster. Wij bevestigen uw reservering en editienummer persoonlijk per e-mail.',
                        )}
                    </p>
                </SuccessPanel>
            ) : step === 1 ? (
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
                        {t('Volgende — Kies uw nummer')}
                    </MaisonButton>
                </form>
            ) : step === 2 ? (
                <div>
                    <p className="mb-3.5 text-[13px] leading-[1.6] text-choc3">
                        {t(
                            'Kies uw voorkeursnummer (1–100). Wij bevestigen persoonlijk of het nog beschikbaar is.',
                        )}
                    </p>

                    <div className="mb-4 grid max-h-[190px] grid-cols-10 gap-1.25 overflow-auto p-1">
                        {EDITION_NUMBERS.map((number) => (
                            <button
                                key={number}
                                type="button"
                                aria-pressed={selectedNumber === number}
                                onClick={() => setSelectedNumber(number)}
                                className={cn(
                                    'aspect-square rounded-sm border border-gold/22 bg-black/3 font-sans text-[10px] text-choc3 transition-colors hover:border-gold hover:text-gold',
                                    selectedNumber === number &&
                                        'border-gold bg-gold text-choc',
                                )}
                            >
                                {number}
                            </button>
                        ))}
                    </div>

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
                                    event.target.value.toUpperCase(),
                                )
                            }
                            placeholder={t('Bijv. YS')}
                            className={cn(
                                modalInputClassName,
                                'max-w-[170px] tracking-[0.2em] uppercase',
                            )}
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
                            disabled={selectedNumber === null}
                            onClick={onStepTwo}
                        >
                            {t('Volgende — Overzicht')}
                        </MaisonButton>
                    </div>
                </div>
            ) : (
                <div>
                    <OrderSummary
                        name={name}
                        email={email}
                        phone={phone}
                        number={selectedNumber}
                        monogram={monogram}
                        giftWrap={giftWrap}
                        giftMessage={giftMessage}
                    />

                    <div className="mb-4 rounded-md border border-gold/25 bg-black/3 p-3.5 ma-sm:p-4">
                        <label className="flex cursor-pointer items-center gap-2.5 font-sans text-xs tracking-[0.12em] text-choc uppercase">
                            <input
                                type="checkbox"
                                checked={giftWrap}
                                onChange={(event) =>
                                    setGiftWrap(event.target.checked)
                                }
                                className="size-4 accent-gold"
                            />
                            {t('Als cadeau verpakken')}
                        </label>

                        {giftWrap && (
                            <div className="mt-3 animate-in fade-in duration-250">
                                <label className="sr-only" htmlFor="gift-msg">
                                    {t(
                                        'Persoonlijke boodschap op de kaart (optioneel)',
                                    )}
                                </label>
                                <textarea
                                    id="gift-msg"
                                    rows={2}
                                    value={giftMessage}
                                    onChange={(event) =>
                                        setGiftMessage(event.target.value)
                                    }
                                    placeholder={t(
                                        'Persoonlijke boodschap op de kaart (optioneel)',
                                    )}
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
                    </div>

                    <div className="flex items-center gap-2.5">
                        <MaisonButton
                            type="button"
                            variant="outlineChoc"
                            onClick={() => setStep(2)}
                        >
                            {t('← Terug')}
                        </MaisonButton>
                        <MaisonButton
                            type="button"
                            variant="filled"
                            block
                            onClick={onPay}
                        >
                            {t('Ga naar betaling — € 249')}
                        </MaisonButton>
                    </div>

                    <p className={cn(modalNoteClassName, 'mt-3')}>
                        {t(
                            'U wordt doorgestuurd naar onze beveiligde betaalpagina. Betaling via Stripe.',
                        )}
                    </p>
                </div>
            )}

            {!paid && (
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
            )}
        </MaisonModal>
    );
}

function OrderSummary({
    name,
    email,
    phone,
    number,
    monogram,
    giftWrap,
    giftMessage,
}: {
    name: string;
    email: string;
    phone: string;
    number: number | null;
    monogram: string;
    giftWrap: boolean;
    giftMessage: string;
}) {
    const { t } = useTranslation();
    const mono = monogram.trim().toUpperCase();
    const trimmedPhone = phone.trim();
    const trimmedMessage = giftMessage.trim();

    return (
        <div className="mb-4 rounded border border-gold/22 bg-black/3 p-4.5">
            <SummaryRow
                label={t('Product')}
                value={t('Heritage No.001 — Founding Edition')}
            />
            <SummaryRow label={t('Houder')} value={name.trim()} />
            {number !== null && (
                <SummaryRow
                    label={t('Voorkeursnummer')}
                    value={`№ ${String(number).padStart(3, '0')} / 100`}
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
            <SummaryRow
                label={t('Totaal')}
                value="€ 249"
                total
            />
            <p className="mt-2.5 font-sans text-[10px] leading-[1.6] tracking-[0.1em] text-choc3 uppercase">
                {t(
                    'Voorkeursnummer en monogram onder voorbehoud — wij bevestigen de beschikbaarheid persoonlijk.',
                )}
            </p>
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
