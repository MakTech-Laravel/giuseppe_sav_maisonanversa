/**
 * Per-product stock figures from edition_pieces or stock_quantity.
 */
export type Edition = {
    reserved: number;
    total: number;
    available: number;
    sellable: number;
    allocated: number;
    archived: number;
    soldOut: boolean;
    deliveryLabel?: string | null;
    productName?: string | null;
};
