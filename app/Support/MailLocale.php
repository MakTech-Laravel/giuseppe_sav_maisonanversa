<?php

namespace App\Support;

class MailLocale
{
    /**
     * Resolve a Maison locale for outbound mail.
     * Empty or unsupported values fall back to the default (nl).
     */
    public static function resolve(?string $locale): string
    {
        $locale = is_string($locale) ? trim($locale) : '';

        /** @var list<string> $supported */
        $supported = config('maison.locales', ['nl']);

        if ($locale !== '' && in_array($locale, $supported, true)) {
            return $locale;
        }

        return (string) config('maison.default_locale', 'nl');
    }
}
