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

    /*
    |--------------------------------------------------------------------------
    | TEMPORARY: admin-type users get every permission
    |--------------------------------------------------------------------------
    |
    | While Access Control is out of the UI, flip this on so any account with
    | `users.type = admin` passes Spatie `permission:*` middleware and sees all
    | nav items. Super-admin Gate::before still applies separately.
    |
    | TURN OFF / DELETE when restoring role-permission management:
    |   - set MAISON_ADMIN_TYPE_GRANTS_ALL_PERMISSIONS=false, or
    |   - remove this key + App\Support\AdminTypePermissionBypass + its wires
    |     in AppServiceProvider and HandleInertiaRequests.
    |
    */

    'admin_type_grants_all_permissions' => (bool) env('MAISON_ADMIN_TYPE_GRANTS_ALL_PERMISSIONS', true),

];
