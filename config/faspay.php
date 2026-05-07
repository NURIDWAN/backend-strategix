<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Faspay Merchant Configuration
    |--------------------------------------------------------------------------
    */
    'merchant_id' => env('FASPAY_MERCHANT_ID', ''),
    'user_id'     => env('FASPAY_USER_ID', ''),
    'password'    => env('FASPAY_PASSWORD', ''),
    'api_key'     => env('FASPAY_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment: "sandbox" or "production"
    |--------------------------------------------------------------------------
    */
    'environment' => env('FASPAY_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'sandbox' => [
            'base_url'    => 'https://xpress-sandbox.faspay.co.id',
            'payment_url' => 'https://xpress-sandbox.faspay.co.id/v4/post',
        ],
        'production' => [
            'base_url'    => 'https://xpress.faspay.co.id',
            'payment_url' => 'https://xpress.faspay.co.id/v4/post',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook URLs
    |--------------------------------------------------------------------------
    */
    'webhook_urls' => [
        'notification' => env('FASPAY_WEBHOOK_NOTIFICATION_URL', ''),
        'return'       => env('FASPAY_WEBHOOK_RETURN_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice expiration in minutes
    |--------------------------------------------------------------------------
    */
    'invoice_expiration' => (int) env('FASPAY_INVOICE_EXPIRATION', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => (bool) env('FASPAY_LOGGING_ENABLED', true),
        'channel' => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported payment channels
    |--------------------------------------------------------------------------
    */
    'supported_channels' => [
        'VA_BCA'     => 'BCA Virtual Account',
        'VA_BNI'     => 'BNI Virtual Account',
        'VA_BRI'     => 'BRI Virtual Account',
        'VA_MANDIRI' => 'Mandiri Virtual Account',
        'VA_PERMATA' => 'Permata Virtual Account',
        'QRIS'       => 'QRIS',
        'GOPAY'      => 'GoPay',
        'OVO'        => 'OVO',
        'DANA'       => 'DANA',
        'LINK_AJA'   => 'LinkAja',
    ],

];
