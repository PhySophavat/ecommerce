<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'mock'),
    'mode' => env('PAYMENT_MODE', 'sandbox'),
    'merchant_id' => env('PAYMENT_MERCHANT_ID'),
    'api_key' => env('PAYMENT_API_KEY'),
    'secret_key' => env('PAYMENT_SECRET_KEY'),
    'base_url' => env('PAYMENT_BASE_URL'),
    'callback_url' => env('PAYMENT_CALLBACK_URL'),
    'frontend_url' => env('FRONTEND_URL', 'http://127.0.0.1:8000/frontend'),
];
