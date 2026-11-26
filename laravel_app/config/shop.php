<?php

return [
    'currency' => 'VND',
    'demo_mode' => (bool) env('SHOP_DEMO_MODE', false),
    'support_email' => env('SHOP_SUPPORT_EMAIL', 'support@example.com'),
    'support_url' => env('SHOP_SUPPORT_URL'),
    // Generic signed adapter. Keep disabled until an actual gateway adapter is verified.
    'payments' => [
        'enabled' => (bool) env('PAYMENT_ENABLED', false) && ! (bool) env('SHOP_DEMO_MODE', false),
        'provider' => env('PAYMENT_PROVIDER', 'bank-adapter'),
        'secret' => env('PAYMENT_WEBHOOK_SECRET'),
        'account' => env('PAYMENT_ACCOUNT_NUMBER'),
        'bank' => env('PAYMENT_BANK_NAME'),
        'holder' => env('PAYMENT_ACCOUNT_HOLDER'),
    ],
];
