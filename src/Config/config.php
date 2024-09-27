<?php

return [
    'public_key' => env('ADDISPAY_PUBLIC_KEY', ''),
    'private_key' => env('ADDISPAY_PRIVATE_KEY', ''),
    'checkout_api_url' => env('ADDISPAY_CHECKOUT_API_URL', 'https://api.addispay.com/checkout'),
    'return_api_url' => env('ADDISPAY_RETURN_API_URL', 'https://yourdomain.com/return'),
    'callback_api_url' => env('ADDISPAY_CALLBACK_API_URL', 'https://yourdomain.com/callback'),
];
