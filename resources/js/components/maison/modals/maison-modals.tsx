import { CertificateModal } from '@/components/maison/modals/certificate-modal';
import { NewsletterModal } from '@/components/maison/modals/newsletter-modal';
import { OrderModal } from '@/components/maison/modals/order-modal';

export type MaisonModalKind = 'newsletter' | 'order' | 'certificate';

type MaisonModalsProps = {
    kind: MaisonModalKind;
    onClose: () => void;
};

export function MaisonModals({ kind, onClose }: MaisonModalsProps) {
    switch (kind) {
        case 'newsletter':
            return <NewsletterModal onClose={onClose} />;
        case 'order':
            return <OrderModal onClose={onClose} />;
        case 'certificate':
            return <CertificateModal onClose={onClose} />;
    }
}
