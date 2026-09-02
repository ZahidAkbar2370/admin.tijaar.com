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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL') . '/auth/facebook/callback'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'pkr_to_usd' => env('STRIPE_PKR_TO_USD', 280),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
    ],

    'jazzcash' => [
        'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
        'password' => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
        'checkout_url' => env('JAZZCASH_CHECKOUT_URL', \App\Services\JazzCashService::DEFAULT_CHECKOUT_URL),
        'mwallet_url' => env('JAZZCASH_MWALLET_URL', \App\Services\JazzCashService::DEFAULT_MWALLET_URL),
        'mwallet_v2_url' => env('JAZZCASH_MWALLET_V2_URL', \App\Services\JazzCashService::DEFAULT_MWALLET_V2_URL),
        'status_inquiry_url' => env('JAZZCASH_STATUS_INQUIRY_URL', \App\Services\JazzCashService::DEFAULT_STATUS_INQUIRY_URL),
        'return_url' => env('APP_URL') . '/api/v1/webhooks/jazzcash/callback',
    ],

    'easypaisa' => [
        'store_id' => env('EASYPAISA_STORE_ID'),
        'hash_key' => env('EASYPAISA_HASH_KEY'),
        'checkout_url' => env('EASYPAISA_CHECKOUT_URL', 'https://easypay.easypaisa.com.pk/easypay/Index.jsf'),
        'postback_url' => env('APP_URL') . '/api/v1/webhooks/easypaisa/callback',
    ],

    'leopards' => [
        'api_key' => env('LEOPARDS_API_KEY'),
        'api_password' => env('LEOPARDS_API_PASSWORD'),
        'environment' => env('LEOPARDS_ENVIRONMENT', 'staging'),
        'staging_url' => env('LEOPARDS_STAGING_URL', 'https://merchantapistaging.leopardscourier.com/api'),
        'production_url' => env('LEOPARDS_PRODUCTION_URL', 'https://merchantapi.leopardscourier.com/api'),
        'shipment_type' => env('LEOPARDS_SHIPMENT_TYPE', 'DETAIN'),
    ],

    'tcs' => [
        'default_rate_pkr' => env('TCS_DEFAULT_RATE_PKR', '250'),
    ],

];
