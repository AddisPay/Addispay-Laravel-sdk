<?php

namespace AddisPay\AddisPaySDK;

use GuzzleHttp\Client;
use AddisPay\AddisPaySDK\Exceptions\AddisPayException;

class AddisPay
{
    protected $config;
    protected $httpClient;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $this->config['checkout_api_url'],
            'timeout'  => 10.0,
        ]);
    }

    /**
     * Initiate a payment.
     *
     * @param array $transactionDetail
     * @param string $publicKey
     * @return string
     * @throws AddisPayException
     */
    public function payNow(array $transactionDetail, string $publicKey): string
    {
        try {
            $response = $this->httpClient->post('', [
                'headers' => [
                    'Authorization' => "Bearer {$publicKey}",
                    'Accept'        => 'application/json',
                ],
                'json' => $transactionDetail,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['payment_url'])) {
                return $body['payment_url'];
            }

            throw new AddisPayException('Invalid response from AddisPay.');
        } catch (\Exception $e) {
            throw new AddisPayException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
