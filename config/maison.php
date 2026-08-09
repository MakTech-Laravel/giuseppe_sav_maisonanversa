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
    | Edition stock
    |--------------------------------------------------------------------------
    |
    | The single source for Heritage No.001 availability. The prototype carried
    | three copies of this figure that had already drifted apart, so the hero
    | counter, the pre-order progress bar and the product prose all read from
    | here instead. A real stock table replaces this entry later.
    |
    */

    'edition' => [
        'reserved' => (int) env('MAISON_EDITION_RESERVED', 73),
        'total' => (int) env('MAISON_EDITION_TOTAL', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration seams
    |--------------------------------------------------------------------------
    |
    | Deliberately empty until the client supplies them, matching the
    | prototype. Checkout and newsletter buttons fall back to their success
    | panels while these are blank.
    |
    */

    'stripe_payment_link' => env('MAISON_STRIPE_PAYMENT_LINK', ''),

    'mailchimp_form_url' => env('MAISON_MAILCHIMP_FORM_URL', ''),

];
