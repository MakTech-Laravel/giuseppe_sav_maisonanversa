import type {
    OrderProductContext,
    PreselectedEdition,
} from '@/components/maison/shell/shell-actions';

/** sessionStorage key for a reservation started before the guest logged in. */
export const PENDING_PURCHASE_KEY = 'pendingPurchase';

/** An abandoned attempt never pops a modal later: it is forgotten after this. */
export const PENDING_PURCHASE_TTL_MS = 30 * 60 * 1000;

/**
 * What a guest was doing when the site asked them to log in: reserving a number
 * (`purchase`, with the number they chose, if any) or ordering a simple product
 * (`order`). Only what the modals need is kept; the server still checks the
 * product, the number and the price when the number is held and when they pay.
 */
export type PendingPurchase = {
    kind: 'purchase' | 'order';
    product?: OrderProductContext;
    edition?: PreselectedEdition;
};

type StoredPendingPurchase = PendingPurchase & { at: number };

export function storePendingPurchase(purchase: PendingPurchase): void {
    try {
        const stored: StoredPendingPurchase = { ...purchase, at: Date.now() };

        sessionStorage.setItem(PENDING_PURCHASE_KEY, JSON.stringify(stored));
    } catch {
        // Storage can be denied; the guest then simply presses Reserve again.
    }
}

export function clearPendingPurchase(): void {
    try {
        sessionStorage.removeItem(PENDING_PURCHASE_KEY);
    } catch {
        // As above.
    }
}

/**
 * Returns the stored reservation once and forgets it, or null when there is none,
 * it has expired, or it cannot be read.
 */
export function takePendingPurchase(): PendingPurchase | null {
    let raw: string | null;

    try {
        raw = sessionStorage.getItem(PENDING_PURCHASE_KEY);
    } catch {
        return null;
    }

    if (raw === null) {
        return null;
    }

    clearPendingPurchase();

    try {
        const stored = JSON.parse(raw) as Partial<StoredPendingPurchase> | null;

        if (
            stored === null ||
            typeof stored.at !== 'number' ||
            Date.now() - stored.at > PENDING_PURCHASE_TTL_MS ||
            (stored.kind !== 'purchase' && stored.kind !== 'order')
        ) {
            return null;
        }

        return {
            kind: stored.kind,
            product: stored.product,
            edition: stored.edition,
        };
    } catch {
        return null;
    }
}
