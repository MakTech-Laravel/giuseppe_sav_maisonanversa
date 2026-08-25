import type { Auth } from '@/types/auth';
import type { Locale } from '@/types/locale';
import type { SeoDocument } from '@/types/seo';

export type CheckoutShared = {
    productId: number | null;
    currency: string;
    amount: string;
    displayAmount: string;
    productName: string;
    deliveryLabel: string | null;
    productType?: 'limited_edition' | 'simple';
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
    announcementText: string | null;
};

export type CommerceShared = {
    shippingEstimateMin: string;
    shippingEstimateMax: string;
    shippingEuIncluded: boolean;
    defaultExpectedDeliveryLabel: string | null;
    pricesIncludeTax: boolean;
};

export type SharedNotificationItem = {
    id: string;
    type: string;
    data: {
        title?: string;
        body?: string;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string | null;
};

export type SharedNotifications = {
    unread_count: number;
    recent: SharedNotificationItem[];
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
            notifications: SharedNotifications | null;
            [key: string]: unknown;
        };
    }
}
