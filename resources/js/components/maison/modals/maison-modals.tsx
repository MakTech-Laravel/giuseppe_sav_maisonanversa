import { AuthModal  } from '@/components/maison/modals/auth-modal';
import type {AuthView} from '@/components/maison/modals/auth-modal';
import { CertificateModal } from '@/components/maison/modals/certificate-modal';
import { NewsletterModal } from '@/components/maison/modals/newsletter-modal';
import { OrderModal } from '@/components/maison/modals/order-modal';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';

export type MaisonModalKind = 'newsletter' | 'order' | 'certificate' | 'auth';

type MaisonModalsProps = {
    kind: MaisonModalKind;
    authView?: AuthView;
    orderProduct?: OrderProductContext;
    onClose: () => void;
    onAuthViewChange?: (view: AuthView) => void;
};

export function MaisonModals({
    kind,
    authView = 'login',
    orderProduct,
    onClose,
    onAuthViewChange = () => undefined,
}: MaisonModalsProps) {
    switch (kind) {
        case 'newsletter':
            return <NewsletterModal onClose={onClose} />;
        case 'order':
            return <OrderModal onClose={onClose} product={orderProduct} />;
        case 'certificate':
            return <CertificateModal onClose={onClose} />;
        case 'auth':
            return (
                <AuthModal
                    initialView={authView}
                    onClose={onClose}
                    onSwitchView={onAuthViewChange}
                />
            );
    }
}
