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
    | Edition stock
    |--------------------------------------------------------------------------
    |
    | Total edition size only. Live reserved/available/sold-out figures come
    | from the edition_pieces table via EditionInventory.
    |
    */

    'edition' => [
        'total' => (int) env('MAISON_EDITION_TOTAL', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout (Cashier / Stripe)
    |--------------------------------------------------------------------------
    |
    | Founding Edition is charged in EUR only via a pre-created Stripe Price.
    | Amount (cents) is for display / local Order records; Checkout charges
    | the Price ID. Adaptive pricing is disabled so buyers always pay euros.
    |
    */

    'checkout' => [
        'currency' => 'eur',
        'amount' => (int) env('MAISON_CHECKOUT_AMOUNT', 24900),
        'product_name' => env('MAISON_CHECKOUT_PRODUCT_NAME', 'Heritage No.001 — Founding Edition'),
        'price_id' => env('MAISON_STRIPE_PRICE_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration seams
    |--------------------------------------------------------------------------
    */

    'mailchimp_form_url' => env('MAISON_MAILCHIMP_FORM_URL', ''),

];
