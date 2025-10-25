<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans API Keys
    |--------------------------------------------------------------------------
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Environment
    |--------------------------------------------------------------------------
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook IP Filtering
    |--------------------------------------------------------------------------
    |
    | Aktifkan pemfilteran IP untuk webhook Midtrans.
    |
    */
    'ip_filter_enabled' => env('MIDTRANS_IP_FILTER_ENABLED', true),

    'allowed_ip_ranges' => [
        // IP Midtrans Sandbox
        '103.208.232.0/22',
        '103.253.212.0/22',

        // IP Midtrans Production (tambahkan jika digunakan)
        '35.240.200.20/32',
        '35.247.92.207/32',
    ],
];
