<?php

return [
    'store_id' => env('SSLCOMMERZ_STORE_ID', 'testbox'),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD', 'qwerty'),
    'sandbox' => (bool) env('SSLCOMMERZ_SANDBOX', true),
    'base_url' => (bool) env('SSLCOMMERZ_SANDBOX', true)
        ? 'https://sandbox.sslcommerz.com'
        : 'https://securepay.sslcommerz.com',
    'timeout' => (int) env('SSLCOMMERZ_TIMEOUT', 15),
];
