<?php

namespace AshenafiPixel\AddisPaySDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use AshenafiPixel\AddisPaySDK\Exceptions\AddisPayException;

class ApiClient
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;

    /**
     * Constructor initializes the Guzzle HTTP client.
     *
     * @param string $apiUrl
     * @param string $apiKey
     * @param string $apiSecret
     */
    public function __construct($apiUrl, $apiKey, $apiSecret)
    {
        $this->client = new Client(['base_uri' => $apiUrl]);
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Send a POST request to the specified endpoint with data.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws AddisPayException
     */
    public function post($endpoint, $data)
    {
        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorMsg = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new AddisPayException("API Request failed: " . $errorMsg);
        }
    }
}
