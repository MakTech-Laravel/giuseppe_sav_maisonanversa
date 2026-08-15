<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'list_heritage_letter' => env('BREVO_LIST_HERITAGE_LETTER'),
        'list_waitlist' => env('BREVO_LIST_WAITLIST'),
        'welcome_via' => env('BREVO_WELCOME_VIA', 'brevo'),
        'smtp' => [
            'host' => env('BREVO_SMTP_HOST', 'smtp-relay.brevo.com'),
            'port' => env('BREVO_SMTP_PORT', 587),
            'username' => env('BREVO_SMTP_USERNAME'),
            'password' => env('BREVO_SMTP_PASSWORD'),
        ],
    ],

    'deepl' => [
        'key' => env('DEEPL_API_KEY'),
        'host' => env('DEEPL_API_HOST'),
        'targets' => [
            'nl' => 'NL',
            'en' => 'EN-GB',
            'fr' => 'FR',
        ],
    ],

];
