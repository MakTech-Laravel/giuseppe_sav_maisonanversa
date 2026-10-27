import { router } from '@inertiajs/react';
import type { i18n as I18n } from 'i18next';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

const TOAST_TYPES = new Set(['success', 'info', 'warning', 'error'] as const);

type ToastType = FlashToast['type'];

let registered = false;
let translator: I18n | null = null;

function translate(key: string, options?: Record<string, unknown>): string {
    if (!translator) {
        return key;
    }

    return String(translator.t(key, options));
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

function resolveFlashToast(flash: unknown): FlashToast | null {
    if (!isRecord(flash) || !isRecord(flash.toast)) {
        return null;
    }

    const message = flash.toast.message;
    const type = flash.toast.type;

    if (typeof message !== 'string' || message.trim() === '') {
        return null;
    }

    if (typeof type !== 'string' || !TOAST_TYPES.has(type as ToastType)) {
        return { type: 'info', message };
    }

    return { type: type as ToastType, message };
}

function showFlashToast(data: FlashToast): void {
    toast[data.type](translate(data.message));
}

function firstValidationMessage(errors: unknown): string | null {
    if (!isRecord(errors)) {
        return null;
    }

    for (const value of Object.values(errors)) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }

        if (Array.isArray(value)) {
            const first = value.find(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            );

            if (first) {
                return first;
            }
        }
    }

    return null;
}

function flashFromEvent(event: Event): unknown {
    if (!('detail' in event)) {
        return event;
    }

    const detail = (event as CustomEvent).detail;

    if (isRecord(detail) && 'flash' in detail) {
        return detail.flash;
    }

    return detail;
}

/**
 * Register once at module scope so Strict Mode remounts of <Toaster /> cannot
 * drop the listener. Pass the app i18n instance so HTTP/network toasts follow
 * the active locale (Dutch source keys).
 */
export function registerFlashToasts(i18n?: I18n): void {
    if (i18n) {
        translator = i18n;
    }

    if (registered || typeof window === 'undefined') {
        return;
    }

    registered = true;

    router.on('flash', (event) => {
        const data = resolveFlashToast(flashFromEvent(event as Event));

        if (data) {
            showFlashToast(data);
        }
    });

    router.on('error', (event) => {
        const detail = (event as CustomEvent).detail;
        const message =
            firstValidationMessage(detail?.errors) ??
            firstValidationMessage(detail);

        if (message) {
            toast.error(translate(message));
        }
    });

    router.on('httpException', (event) => {
        const response = (event as CustomEvent).detail?.response;
        const status = response?.status;
        const message =
            typeof status === 'number'
                ? translate('Verzoek mislukt ({{status}})', { status })
                : translate('Verzoek mislukt');

        toast.error(message);
    });

    router.on('networkError', () => {
        toast.error(translate('Netwerkfout. Probeer het opnieuw.'));
    });
}

/** Kept for the Toaster mount path; registration is idempotent. */
export function useFlashToast(): void {
    registerFlashToasts();
}
