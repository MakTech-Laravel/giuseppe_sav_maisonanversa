import { useState  } from 'react';
import type {FormEvent} from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonModal, modalInputClassName, modalNoteClassName } from '@/components/maison/modals/maison-modal';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { SuccessPanel } from '@/components/maison/ui/success-panel';

type NewsletterModalProps = {
    onClose: () => void;
};

/**
 * The Heritage Letter signup modal. Until Mailchimp is wired, a valid address
 * shows the success panel locally — the same soft landing the prototype used
 * when its form URL was empty.
 */
export function NewsletterModal({ onClose }: NewsletterModalProps) {
    const { t } = useTranslation();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function onSubmit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        setError(null);

        const trimmed = email.trim();

        if (!trimmed || !trimmed.includes('@')) {
            setError(t('Vul een geldig e-mailadres in.'));

            return;
        }

        setSent(true);
    }

    return (
        <MaisonModal label="De Heritage Letter" onClose={onClose}>
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {t('De Heritage Letter')}
            </h2>
            <span className="mb-6 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {t('Vroege toegang · Exclusieve updates · Gratis')}
            </span>

            {sent ? (
                <SuccessPanel
                    title={t('Welkom bij Maison Anversa.')}
                    icon="✓"
                >
                    <p>
                        {t(
                            'U ontvangt binnenkort een bevestiging en als eerste toegang tot Heritage No.001.',
                        )}
                    </p>
                </SuccessPanel>
            ) : (
                <>
                    <p className="mb-7 text-[15px] leading-[1.8] text-choc3">
                        {t(
                            'Schrijf u in en ontvang als eerste toegang tot Heritage No.001 en alle toekomstige releases van Maison Anversa.',
                        )}
                    </p>

                    <form
                        onSubmit={onSubmit}
                        className="flex flex-col gap-3.5"
                        noValidate
                    >
                        <label className="sr-only" htmlFor="nl-modal-name">
                            {t('Uw naam')}
                        </label>
                        <input
                            id="nl-modal-name"
                            type="text"
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            placeholder={t('Uw naam')}
                            className={modalInputClassName}
                        />

                        <label className="sr-only" htmlFor="nl-modal-email">
                            {t('Uw e-mailadres')}
                        </label>
                        <input
                            id="nl-modal-email"
                            type="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder={t('Uw e-mailadres')}
                            className={modalInputClassName}
                        />

                        {error && (
                            <p role="alert" className="text-[13px] text-choc3">
                                {error}
                            </p>
                        )}

                        <MaisonButton type="submit" variant="filled" block>
                            {t('Schrijf In')}
                        </MaisonButton>
                    </form>
                </>
            )}

            <p className={modalNoteClassName}>
                {t(
                    'Geen spam. Alleen wat er echt toe doet. Uitschrijven kan altijd.',
                )}
            </p>
        </MaisonModal>
    );
}
