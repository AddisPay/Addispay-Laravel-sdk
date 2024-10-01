<?php

namespace AshenafiPixel\AddisPaySDK;

use AshenafiPixel\AddisPaySDK\Exceptions\AddisPayException;

class AddisPay
{
    protected $apiClient;
    protected $publicKey;
    protected $privateKey;

    /**
     * Constructor initializes the API client with configuration.
     *
     * @throws AddisPayException
     */
    public function __construct()
    {
        $config = Config::get();
        $this->publicKey = $config['public_api_key'];
        $this->privateKey = $config['private_key'];
        $this->apiClient = new ApiClient($config['checkout_api_url'], $config['public_api_key'], $config['private_key']);

        if (empty($this->publicKey) || empty($this->privateKey)) {
            throw new AddisPayException("AddisPay API keys are not set.");
        }
    }

    /**
     * Create a payment order.
     *
     * @param array $paymentData
     * @return array
     * @throws AddisPayException
     */
    public function createPayment(array $paymentData)
    {
        // Validate required fields
        $required = [
            'total_amount', 'tx_ref', 'currency', 'first_name',
            'last_name', 'email', 'phone_number', 'session_expired',
            'nonce', 'order_detail', 'success_url', 'cancel_url',
            'error_url', 'message'
        ];

        foreach ($required as $field) {
            if (!isset($paymentData[$field])) {
                throw new AddisPayException("Missing required field: {$field}");
            }
        }

        // Encrypt necessary fields
        $encryptedData = [
            'data' => [
                'total_amount' => $paymentData['total_amount'], // Assuming no encryption for amount
                'tx_ref' => $paymentData['tx_ref'], // Assuming no encryption
                'currency' => Encryption::encrypt($paymentData['currency'], $this->publicKey),
                'first_name' => Encryption::encrypt($paymentData['first_name'], $this->publicKey),
                'email' => Encryption::encrypt($paymentData['email'], $this->publicKey),
                'phone_number' => Encryption::encrypt($paymentData['phone_number'], $this->publicKey),
                'last_name' => Encryption::encrypt($paymentData['last_name'], $this->publicKey),
                'session_expired' => Encryption::encrypt((string)$paymentData['session_expired'], $this->publicKey),
                'nonce' => Encryption::encrypt($paymentData['nonce'], $this->publicKey),
                'order_detail' => [
                    'items' => $paymentData['order_detail']['items'],
                    'description' => $paymentData['order_detail']['description'],
                ],
                'success_url' => Encryption::encrypt($paymentData['success_url'], $this->publicKey),
                'cancel_url' => Encryption::encrypt($paymentData['cancel_url'], $this->publicKey),
                'error_url' => Encryption::encrypt($paymentData['error_url'], $this->publicKey),
            ],
            'message' => Encryption::encrypt($paymentData['message'], $this->publicKey),
        ];

        // Make the API request
        $response = $this->apiClient->post('/api/v1/receive-data', $encryptedData);

        if (isset($response['checkout_url']) && isset($response['uuid'])) {
            return [
                'checkout_url' => $response['checkout_url'],
                'uuid' => $response['uuid'],
                'amount' => $response['amount'] ?? null,
                'status' => $response['status'] ?? null,
                'isCommissioned' => $response['isCommissioned'] ?? null,
            ];
        }

        throw new AddisPayException("Invalid response from AddisPay API.");
    }

    /**
     * Handle callback from AddisPay.
     *
     * @param array $callbackData
     * @return array
     * @throws AddisPayException
     */
    public function handleCallback(array $callbackData)
    {
        try {
            // Decrypt necessary fields
            $decryptedData = [
                'payment_id' => Encryption::decrypt($callbackData['payment_id'], $this->privateKey),
                'status' => Encryption::decrypt($callbackData['status'], $this->privateKey),
                // Add other fields as necessary
            ];

            return $decryptedData;
        } catch (\Exception $e) {
            throw new AddisPayException("Callback handling failed: " . $e->getMessage());
        }
    }
}
