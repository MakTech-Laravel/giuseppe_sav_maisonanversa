import type { Auth } from '@/types/auth';
import type { Locale } from '@/types/locale';
import type { SeoDocument } from '@/types/seo';

export type CheckoutShared = {
    productId: number | null;
    productSlug?: string | null;
    currency: string;
    amount: string;
    displayAmount: string;
    productName: string;
    deliveryLabel: string | null;
    productType?: 'limited_edition' | 'simple';
    editionPieceId?: number | null;
    editionNumber?: number | null;
    editionLabel?: string | null;
};

export type SiteShared = {
    phone: string;
    whatsapp: string;
    emailHello: string;
    emailPress: string;
    instagramUrl: string;
    boutiqueLat: number;
    boutiqueLng: number;
    boutiqueMapSrc: string;
    whatsappHref: string;
    phoneHref: string;
    emailHelloHref: string;
};

export type CommerceShared = {
    shippingEstimateMin: string;
    shippingEstimateMax: string;
    shippingEuIncluded: boolean;
    defaultExpectedDeliveryLabel: string | null;
    pricesIncludeTax: boolean;
};

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: Locale;
            availableLocales: Locale[];
            availableImages: string[];
            checkout: CheckoutShared;
            commerce: CommerceShared;
            site: SiteShared;
            seo: SeoDocument;
            [key: string]: unknown;
        };
    }
}
