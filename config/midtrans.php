<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Server Key
    |--------------------------------------------------------------------------
    |
    | Your Midtrans server key from Midtrans Dashboard
    |
    */
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Client Key
    |--------------------------------------------------------------------------
    |
    | Your Midtrans client key from Midtrans Dashboard
    |
    */
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Merchant ID
    |--------------------------------------------------------------------------
    |
    | Your Midtrans Merchant ID
    |
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Production Mode
    |--------------------------------------------------------------------------
    |
    | Set to true if you want to use production mode
    |
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Sanitized
    |--------------------------------------------------------------------------
    |
    | Set to true if you want to sanitize input
    |
    */
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),

    /*
    |--------------------------------------------------------------------------
    | 3DS
    |--------------------------------------------------------------------------
    |
    | Set to true if you want to enable 3D Secure
    |
    */
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Notification URL
    |--------------------------------------------------------------------------
    |
    | URL for Midtrans to send notification
    |
    */
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL', env('APP_URL') . '/api/payments/notification'),

    /*
    |--------------------------------------------------------------------------
    | Finish URL
    |--------------------------------------------------------------------------
    |
    | URL to redirect after payment is finished
    |
    */
    'finish_url' => env('MIDTRANS_FINISH_URL', env('APP_URL') . '/payment/finish'),
];