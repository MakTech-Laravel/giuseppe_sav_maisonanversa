<?php

namespace App\Support;

class MailLocale
{
    /**
     * Resolve a Maison locale for outbound mail and notifications.
     * Empty or unsupported values fall back to the notification locale (en).
     */
    public static function resolve(?string $locale): string
    {
        $locale = is_string($locale) ? trim($locale) : '';

        /** @var list<string> $supported */
        $supported = config('maison.locales', ['nl']);

        if ($locale !== '' && in_array($locale, $supported, true)) {
            return $locale;
        }

        return (string) config('maison.notification_fallback_locale', 'en');
    }
}
