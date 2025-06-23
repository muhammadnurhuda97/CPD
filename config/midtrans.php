<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans API Keys
    |--------------------------------------------------------------------------
    |
    | Kunci-kunci ini didapat dari dashboard Midtrans Anda.
    |
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Environment
    |--------------------------------------------------------------------------
    |
    | Setel ke 'true' untuk mode Produksi atau 'false' untuk Sandbox.
    |
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),
];
