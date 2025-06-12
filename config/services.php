<?php

$stripeIps = json_decode(file_get_contents(resource_path('stripeWebhookIPs.json')), true);

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'api_key' => env('STRIPE_API_KEY'),
        'payment_method_configuration' => env('STRIPE_PAYMENT_METHOD_CONFIG'),
        'tax_rates' => env('STRIPE_TAX_RATES', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_ips' => $stripeIps,
        'sales_tax_rate' => env('SALES_TAX_RATE', 0),
        'stripe_fee_percentage' => env('STRIPE_FEE_PERCENTAGE', 0),
        'stripe_fee_flat' => env('STRIPE_FEE_FLAT', 0),
        'dashboard_url' => env('STRIPE_DASHBOARD_URL', 'https://dashboard.stripe.com'),

    ],

];
