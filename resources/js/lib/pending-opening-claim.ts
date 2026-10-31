/** sessionStorage key for a homepage Claim started before the guest authenticated. */
export const PENDING_OPENING_CLAIM_KEY = 'pendingOpeningClaim';

export type PendingOpeningClaim = {
    productSlug: string;
    editionPieceId: number;
    editionNumber: number;
    editionLabel: string;
};

export function writePendingOpeningClaim(claim: PendingOpeningClaim): void {
    sessionStorage.setItem(PENDING_OPENING_CLAIM_KEY, JSON.stringify(claim));
}

export function readPendingOpeningClaim(): PendingOpeningClaim | null {
    const raw = sessionStorage.getItem(PENDING_OPENING_CLAIM_KEY);

    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as PendingOpeningClaim;

        if (
            typeof parsed.productSlug !== 'string' ||
            typeof parsed.editionPieceId !== 'number' ||
            typeof parsed.editionNumber !== 'number' ||
            typeof parsed.editionLabel !== 'string'
        ) {
            return null;
        }

        return parsed;
    } catch {
        return null;
    }
}

export function clearPendingOpeningClaim(): void {
    sessionStorage.removeItem(PENDING_OPENING_CLAIM_KEY);
}
