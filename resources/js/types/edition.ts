/**
 * Heritage No.001 stock figures from the edition_pieces table.
 */
export type Edition = {
    reserved: number;
    total: number;
    available: number;
    sellable: number;
    allocated: number;
    archived: number;
    soldOut: boolean;
};
