import { AuthModal } from '@/components/maison/modals/auth-modal';
import type { AuthView } from '@/components/maison/modals/auth-modal';
import { CertificateModal } from '@/components/maison/modals/certificate-modal';
import { EditionPickerModal } from '@/components/maison/modals/edition-picker-modal';
import { NewsletterModal } from '@/components/maison/modals/newsletter-modal';
import { OrderModal } from '@/components/maison/modals/order-modal';
import type { OrderProductContext } from '@/components/maison/shell/shell-actions';

export type MaisonModalKind =
    | 'newsletter'
    | 'order'
    | 'edition-picker'
    | 'certificate'
    | 'auth';

type MaisonModalsProps = {
    kind: MaisonModalKind;
    authView?: AuthView;
    orderProduct?: OrderProductContext;
    onClose: () => void;
    onAuthViewChange?: (view: AuthView) => void;
    onEditionSelected?: (product: OrderProductContext) => void;
};

export function MaisonModals({
    kind,
    authView = 'login',
    orderProduct,
    onClose,
    onAuthViewChange = () => undefined,
    onEditionSelected = () => undefined,
}: MaisonModalsProps) {
    switch (kind) {
        case 'newsletter':
            return <NewsletterModal onClose={onClose} />;
        case 'edition-picker':
            if (!orderProduct) {
                return null;
            }

            return (
                <EditionPickerModal
                    product={orderProduct}
                    onClose={onClose}
                    onSelect={({ editionPieceId, editionNumber, editionLabel }) =>
                        onEditionSelected({
                            ...orderProduct,
                            editionPieceId,
                            editionNumber,
                            editionLabel,
                        })
                    }
                />
            );
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
