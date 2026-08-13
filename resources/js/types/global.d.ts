import type { Auth } from '@/types/auth';
import type { Locale } from '@/types/locale';

export type CheckoutShared = {
    currency: string;
    amount: number;
    displayAmount: string;
    productName: string;
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
            [key: string]: unknown;
        };
    }
}
