<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'server_key' => env('FCM_SERVER_KEY'),
        'credentials_file' => env('FCM_CREDENTIALS_FILE'),
    ],

    'turn' => [
        'url' => env('TURN_SERVER_URL'),
        'secret' => env('TURN_SERVER_SECRET'),
    ],

    'livekit' => [
        'url' => env('LIVEKIT_SERVER_URL', 'wss://livekit.jugajug.com'),
        'api_key' => env('LIVEKIT_API_KEY', 'devkey'),
        'api_secret' => env('LIVEKIT_API_SECRET', 'secret_livekit_key_for_jugajug_2026'),
    ],

    'bkash' => [
        'app_key' => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username' => env('BKASH_USERNAME'),
        'password' => env('BKASH_PASSWORD'),
        'sandbox' => env('BKASH_SANDBOX', true),
        'base_url' => env('BKASH_BASE_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'),
    ],

    'nagad' => [
        'merchant_id' => env('NAGAD_MERCHANT_ID'),
        'public_key' => env('NAGAD_PUBLIC_KEY'),
        'private_key' => env('NAGAD_PRIVATE_KEY'),
        'sandbox' => env('NAGAD_SANDBOX', true),
        'base_url' => env('NAGAD_BASE_URL', 'https://api-sandbox.nagad.com.bd'),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        'base_url' => env('SSLCOMMERZ_BASE_URL', 'https://sandbox.sslcommerz.com'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'marketplace_payments' => [
        'webhook_secret' => env('MARKETPLACE_PAYMENT_WEBHOOK_SECRET', 'bondhoo_marketplace_secret_key_2026'),
        'sandbox_mode' => env('MARKETPLACE_PAYMENT_SANDBOX', true),
    ],

];
