/**
 * Prototype Founding Circle member session, kept in the browser until a real
 * member system replaces it. The key name matches the prototype so a visitor
 * who already logged in there does not lose their session.
 */
export const FC_MEMBER_KEY = 'fc_member';

export type FcMember = {
    num: number;
    email: string;
};

function storage(): Storage | null {
    try {
        return window.localStorage;
    } catch {
        return null;
    }
}

export function readFcMember(): FcMember | null {
    const ls = storage();

    if (!ls) {
        return null;
    }

    try {
        const raw = ls.getItem(FC_MEMBER_KEY);
        const parsed = raw ? (JSON.parse(raw) as FcMember) : null;

        if (
            parsed &&
            typeof parsed.num === 'number' &&
            typeof parsed.email === 'string' &&
            parsed.num >= 1 &&
            parsed.num <= 100
        ) {
            return parsed;
        }
    } catch {
        return null;
    }

    return null;
}

export function writeFcMember(member: FcMember): void {
    const ls = storage();

    if (!ls) {
        return;
    }

    try {
        ls.setItem(FC_MEMBER_KEY, JSON.stringify(member));
    } catch {
        // Private mode can reject writes; the in-memory React state still works.
    }
}

export function clearFcMember(): void {
    const ls = storage();

    if (!ls) {
        return;
    }

    try {
        ls.removeItem(FC_MEMBER_KEY);
    } catch {
        // ignore
    }
}

export function formatEditionNumber(num: number): string {
    return String(num).padStart(3, '0');
}

export function readReferralFromSearch(search: string): number | null {
    try {
        const raw = new URLSearchParams(search).get('ref');

        if (!raw) {
            return null;
        }

        const n = Number.parseInt(raw, 10);

        if (!Number.isFinite(n) || n < 1) {
            return null;
        }

        return n;
    } catch {
        return null;
    }
}
