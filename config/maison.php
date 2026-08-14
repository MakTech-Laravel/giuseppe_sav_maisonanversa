<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Dutch is the source language: every translation key in lang/*.json is the
    | Dutch copy itself, so `nl` needs no dictionary. The order here is the
    | order the language switcher renders.
    |
    */

    'locales' => ['nl', 'en', 'fr'],

    'default_locale' => 'nl',

    /*
    |--------------------------------------------------------------------------
    | Locale preference cookie
    |--------------------------------------------------------------------------
    |
    | Survives logout and bare `/` visits. Unencrypted so preference can be
    | read without the app key. Resolution: cookie → session → Accept-Language.
    |
    */

    'locale_cookie' => 'maison_locale',

    'locale_cookie_minutes' => (int) env('MAISON_LOCALE_COOKIE_MINUTES', 60 * 24 * 365),

    /*
    |--------------------------------------------------------------------------
    | Public pages
    |--------------------------------------------------------------------------
    |
    | Slugs are canonical rather than translated, so the same path resolves
    | under every locale prefix. Route names are `maison.{page}`.
    |
    */

    'pages' => [
        'home' => '',
        'house' => 'huis',
        'product' => 'product',
        'story' => 'story',
        'circle' => 'circle',
        'dressing' => 'dressing',
        'journal' => 'journal',
        'community' => 'community',
        'corner' => 'corner',
        'contact' => 'contact',
        'privacy' => 'privacy',
        'terms' => 'terms',
        'shipping' => 'shipping',
        'care' => 'care',
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO defaults
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'image' => '/images/rooms/room-entrance.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Live reserved/available/sold-out figures come from each product's
    | edition_pieces (limited editions) or stock_quantity (simple products).
    |
    */

    'checkout' => [
        'currency' => 'eur',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration seams
    |--------------------------------------------------------------------------
    */

    'mailchimp_form_url' => env('MAISON_MAILCHIMP_FORM_URL', ''),

];
