<?php

namespace AshenafiPixel\AddisPaySDK\Config;

class Config
{
    /**
     * Retrieve the configuration settings for AddisPay SDK.
     *
     * @return array
     */
    public static function get()
    {
        return [
            'public_api_key' => env('ADDISPAY_PUBLIC_API_KEY', ''),
            'private_key' => env('ADDISPAY_PRIVATE_KEY', ''),
            'checkout_api_url' => env('ADDISPAY_CHECKOUT_API_URL', 'https://checkoutapi.addispay.et/api/v1/receive-data'),
            'return_api_url' => env('ADDISPAY_RETURN_API_URL', 'https://yourdomain.com/return'),
            'callback_api_url' => env('ADDISPAY_CALLBACK_API_URL', 'https://yourdomain.com/callback'),
        ];
    }
}
