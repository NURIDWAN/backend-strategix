<?php

/**
 * SINGAPAY B2B PAYMENT GATEWAY CONFIGURATION
 * For Export PDF Pro Payment System
 *
 * @see https://docs-payment-b2b.singapay.id
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Operating Mode
    |--------------------------------------------------------------------------
    | Supported: "mock", "sandbox", "production"
    | - mock: For local development, no real API calls
    | - sandbox: For testing with Singapay sandbox environment
    | - production: For live transactions
    */
    'mode' => env('SINGAPAY_MODE', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    | Get these from Singapay Merchant Dashboard
    */
    'partner_id' => env('SINGAPAY_PARTNER_ID'),
    'client_id' => env('SINGAPAY_CLIENT_ID'),
    'client_secret' => env('SINGAPAY_CLIENT_SECRET'),
    'merchant_account_id' => env('SINGAPAY_MERCHANT_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Invoice Prefix Configuration
    |--------------------------------------------------------------------------
    | Custom prefix for transaction codes to identify notifications
    | Examples: "GRPD" for Grapadi PDF, "GRCS" for Grapadi Consultation
    */
    'invoice_prefix' => env('SINGAPAY_INVOICE_PREFIX', 'GRPD'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */
    'sandbox_url' => env('SINGAPAY_SANDBOX_URL', 'https://sandbox-payment-b2b.singapay.id'),
    'production_url' => env('SINGAPAY_PRODUCTION_URL', 'https://payment-b2b.singapay.id'),

    /*
    |--------------------------------------------------------------------------
    | PDF Payment Packages
    |--------------------------------------------------------------------------
    | Default package configurations (also seeded in database)
    */
    'packages' => [
        'monthly' => [
            'name' => 'Paket Bulanan',
            'duration_days' => 30,
            'price' => 200000, // Rp 200,000
            'description' => 'Export PDF Pro tanpa watermark - 30 hari',
        ],
        'yearly' => [
            'name' => 'Paket Tahunan',
            'duration_days' => 365,
            'price' => 1680000, // Rp 1,680,000 (hemat 30%)
            'description' => 'Export PDF Pro tanpa watermark - 365 hari (Hemat 30%)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Virtual Account Configuration
    |--------------------------------------------------------------------------
    */
    'virtual_account' => [
        'banks' => ['BRI', 'BNI', 'MANDIRI', 'PERMATA', 'DANAMON', 'CIMB', 'BSI'],
        'expiry_hours' => env('SINGAPAY_VA_EXPIRY_HOURS', 24),
        'kind' => env('SINGAPAY_VA_KIND', 'temporary'), // temporary or permanent
        'max_usage' => env('SINGAPAY_VA_MAX_USAGE', 1), // 1-255 for temporary VA
        'min_amount' => env('SINGAPAY_VA_MIN_AMOUNT', 10000), // Rp 10,000 (Singapay minimum)
        'max_amount' => env('SINGAPAY_VA_MAX_AMOUNT', 100000000), // Rp 100,000,000 (Singapay maximum)
    ],

    /*
    |--------------------------------------------------------------------------
    | QRIS Configuration
    |--------------------------------------------------------------------------
    */
    'qris' => [
        'expiry_hours' => env('SINGAPAY_QRIS_EXPIRY_HOURS', 1),
        'enable_tip' => env('SINGAPAY_QRIS_ENABLE_TIP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'secret' => env('SINGAPAY_WEBHOOK_SECRET'),
        'url' => env('SINGAPAY_WEBHOOK_URL', env('APP_URL') . '/api/webhook/singapay/payment'),
        'signature_validation' => env('SINGAPAY_WEBHOOK_SIGNATURE_VALIDATION', true),
        'ip_whitelist' => env('SINGAPAY_WEBHOOK_IP_WHITELIST', ''), // Comma-separated IPs
    ],

    /*
    |--------------------------------------------------------------------------
    | Mock Configuration (for local testing)
    |--------------------------------------------------------------------------
    */
    'mock' => [
        // Auto-approve payment after X seconds (0 = disabled)
        'auto_approve_delay' => env('SINGAPAY_MOCK_AUTO_APPROVE_DELAY', 5),

        // Success rate percentage (0-100)
        'success_rate' => env('SINGAPAY_MOCK_SUCCESS_RATE', 100),

        // Simulate API response delay in seconds
        'response_delay' => env('SINGAPAY_MOCK_RESPONSE_DELAY', 1),

        // Enable detailed mock logging
        'verbose_logging' => env('SINGAPAY_MOCK_VERBOSE_LOGGING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Access token caching to reduce API calls
    */
    'cache' => [
        'enabled' => env('SINGAPAY_CACHE_ENABLED', true),
        'ttl' => env('SINGAPAY_CACHE_TTL', 3000), // 50 minutes (token expires in 60)
        'prefix' => 'singapay_token_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('SINGAPAY_LOGGING', true),
        'channel' => env('SINGAPAY_LOG_CHANNEL', 'daily'),
        'level' => env('SINGAPAY_LOG_LEVEL', 'info'), // debug, info, warning, error
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout & Retry Configuration
    |--------------------------------------------------------------------------
    */
    'timeout' => env('SINGAPAY_TIMEOUT', 30), // seconds

    'retry' => [
        'max_attempts' => env('SINGAPAY_RETRY_MAX_ATTEMPTS', 3),
        'delay' => env('SINGAPAY_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'enable_payment_link' => env('SINGAPAY_ENABLE_PAYMENT_LINK', false),
        'enable_disbursement' => env('SINGAPAY_ENABLE_DISBURSEMENT', false),
        'enable_qris' => env('SINGAPAY_ENABLE_QRIS', true),
        'enable_virtual_account' => env('SINGAPAY_ENABLE_VIRTUAL_ACCOUNT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Payment Switch
    |--------------------------------------------------------------------------
    | Set SINGAPAY_PAYMENT_ENABLED=false to disable the purchase endpoint.
    | Info, status, history, and cancel remain accessible.
    */
    'payment_enabled' => env('SINGAPAY_PAYMENT_ENABLED', true),
];
