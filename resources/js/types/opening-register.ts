export type OpeningRegisterPlace = {
    sequence: number;
    label: string;
    edition_piece_id: number | null;
    reserved: boolean;
};

export type OpeningRegisterProduct = {
    productId: number | null;
    productSlug?: string | null;
    currency: string;
    amount: string;
    displayAmount: string;
    productName: string;
    deliveryLabel: string | null;
    productType?: 'limited_edition' | 'simple';
    editionTotal: number;
};

export type OpeningRegister = {
    product: OpeningRegisterProduct;
    available_count: number;
    places: OpeningRegisterPlace[];
};
