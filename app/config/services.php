<?php

return [
    'envato' => [
        'personal_token' => env('ENVATO_PERSONAL_TOKEN'),
        'item_id' => env('ENVATO_ITEM_ID'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'sandbox' => env('PAYPAL_SANDBOX', true),
    ],
];
