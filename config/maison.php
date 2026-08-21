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
        'image_width' => 1024,
        'image_height' => 682,
        'sitemap_cache_seconds' => (int) env('MAISON_SITEMAP_CACHE_SECONDS', 3600),
        'disallow_paths' => [
            '/login',
            '/register',
            '/forgot-password',
            '/two-factor-challenge',
            '/telescope',
        ],
        'disallow_locale_segments' => [
            'admin',
            'member',
            'settings',
            'checkout',
            'file-upload-demo',
            'verify',
        ],
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

    'corner_form_options' => [
        'court_options' => ['4-6 courts', '7-10 courts', '10+ courts'],
        'format_options' => [
            'Formaat A — Heritage Corner',
            'Formaat B — Founding Club Corner',
            'Nog niet beslist',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration seams
    |--------------------------------------------------------------------------
    */

    'mailchimp_form_url' => env('MAISON_MAILCHIMP_FORM_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | TEMPORARY: admin-type users get every permission
    |--------------------------------------------------------------------------
    |
    | While Access Control is out of the UI, flip this on so any account with
    | `users.type = admin` passes Spatie `permission:*` middleware and sees all
    | nav items. Super-admin Gate::before still applies separately. Model
    | policies (e.g. editing another super-admin) are NOT bypassed.
    |
    | TURN OFF / DELETE when restoring role-permission management:
    |   - set MAISON_ADMIN_TYPE_GRANTS_ALL_PERMISSIONS=false, or
    |   - remove this key + App\Support\AdminTypePermissionBypass + its wires
    |     in AppServiceProvider and HandleInertiaRequests.
    |
    */

    'admin_type_grants_all_permissions' => (bool) env('MAISON_ADMIN_TYPE_GRANTS_ALL_PERMISSIONS', true),

];
