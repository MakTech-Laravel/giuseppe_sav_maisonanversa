import { useTranslation } from 'react-i18next';
import { HeritageLetterForm } from '@/components/maison/heritage-letter-form';
import {
    MaisonModal,
    modalNoteClassName,
} from '@/components/maison/modals/maison-modal';

type NewsletterModalProps = {
    onClose: () => void;
};

export function NewsletterModal({ onClose }: NewsletterModalProps) {
    const { t } = useTranslation();

    return (
        <MaisonModal label="De Heritage Letter" onClose={onClose}>
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {t('The Heritage Letter')}
            </h2>
            <span className="mb-6 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {t('Early access · Exclusive updates · Free')}
            </span>

            <p className="mb-7 text-[15px] leading-[1.8] text-choc3">
                {t(
                    'Sign up and be the first to access Heritage Nº 001 and all future releases from Maison Anversa.',
                )}
            </p>

            <HeritageLetterForm
                source="modal"
                showName
                variant="light"
                className="[&_input]:placeholder:text-stone"
                onSuccess={onClose}
            />

            <p className={modalNoteClassName}>
                {t(
                    'No spam. Only what truly matters. Unsubscribe anytime.',
                )}
            </p>
        </MaisonModal>
    );
}
