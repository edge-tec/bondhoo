<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Gateway Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "ssl_wireless", "bulksmsbd", "twilio", "infobip", "log"
    |
    */
    'default' => env('SMS_GATEWAY_DRIVER', 'log'),

    'gateways' => [
        'ssl_wireless' => [
            'api_token' => env('SSL_WIRELESS_API_TOKEN', ''),
            'sid' => env('SSL_WIRELESS_SID', ''),
            'csms_id' => env('SSL_WIRELESS_CSMS_ID', 'JUGAJUG'),
            'endpoint' => env('SSL_WIRELESS_ENDPOINT', 'https://smsplus.sslwireless.com/api/v3/send-sms'),
        ],

        'bulksmsbd' => [
            'api_key' => env('BULKSMSBD_API_KEY', ''),
            'sender_id' => env('BULKSMSBD_SENDER_ID', 'JUGAJUG'),
            'endpoint' => env('BULKSMSBD_ENDPOINT', 'http://bulksmsbd.net/api/smsapi'),
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
            'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
            'from' => env('TWILIO_FROM_NUMBER', ''),
        ],

        'infobip' => [
            'api_key' => env('INFOBIP_API_KEY', ''),
            'base_url' => env('INFOBIP_BASE_URL', ''),
            'from' => env('INFOBIP_FROM', 'JUGAJUG'),
        ],

        'log' => [
            // Safe logging driver for development and testing
        ],
    ],
];
