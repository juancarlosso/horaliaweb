<?php

return [

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
    ],

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

    'cloudflare' => [
        'turnstile_sitekey' => env('CLOUDFLARE_SITEKEY'),
        'turnstile_secretkey' => env('CLOUDFLARE_SECRETKEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_SECRET'),
        'publishable_key' => env('STRIPE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'sifei' => [
        'usuario' => env('SIFEI_USUARIO'),
        'password' => env('SIFEI_PASSWORD'),
        'id_equipo' => env('SIFEI_ID_EQUIPO'),
        'url_timbrar' => env('SIFEI_URL_TIMBRAR'),
        'url_cancelar' => env('SIFEI_URL_CANCELAR'),
        'modo' => env('SIFEI_MODO', 'pruebas'),
        'password_csd_emisor' => env('SIFEI_PWD_CSD_EMISOR'),
    ],

];
