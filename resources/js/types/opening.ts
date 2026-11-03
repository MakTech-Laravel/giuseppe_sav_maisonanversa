import type { OrderProductContext } from '@/components/maison/shell/shell-actions';

/** One edition number in the opening's register. */
export type OpeningTile = {
    id: number;
    /** The number as the edition picker prints it, e.g. 007 or EXC007ST. */
    label: string;
    /** False for numbers that are held, sold, returning or not for sale. */
    available: boolean;
};

/**
 * The product the admin chose for the opening, and its edition numbers. Null when
 * no offer is active or its product can no longer be offered.
 */
export type OpeningRegisterData = {
    /** The checkout context handed to `openPurchase()`. */
    product: OrderProductContext;
    name: string;
    total: number;
    available: number;
    tiles: OpeningTile[];
};
