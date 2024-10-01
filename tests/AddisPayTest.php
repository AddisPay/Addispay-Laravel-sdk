<?php

namespace AshenafiPixel\AddisPaySDK\Tests;

use AshenafiPixel\AddisPaySDK\AddisPay;
use AshenafiPixel\AddisPaySDK\Exceptions\AddisPayException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class AddisPayTest extends TestCase
{
    protected $addisPay;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Guzzle client
        $this->mockClient = Mockery::mock(Client::class);

        // Create an instance of AddisPay with mocked ApiClient
        $this->addisPay = new AddisPay();

        // Use Reflection to set the ApiClient's client to the mocked client
        $reflection = new \ReflectionClass($this->addisPay);
        $apiClientProperty = $reflection->getProperty('apiClient');
        $apiClientProperty->setAccessible(true);

        // Create a partial mock of ApiClient to inject the mocked Guzzle client
        $apiClientMock = Mockery::mock(\AshenafiPixel\AddisPaySDK\ApiClient::class, [
            'post' => null, // We'll set expectations later
        ]);

        // Set the mocked ApiClient
        $apiClientProperty->setValue($this->addisPay, $apiClientMock);
    }

    public function testCreatePaymentSuccess()
    {
        $paymentData = [
            'total_amount' => 100,
            'tx_ref' => 'transaction_reference',
            'currency' => 'ETB',
            'first_name' => 'Tilahun',
            'last_name' => 'Feyissa',
            'email' => 'sample@gmail.com',
            'phone_number' => '900000000',
            'session_expired' => 5000,
            'nonce' => 'uniqueID',
            'order_detail' => [
                'items' => 'rfid',
                'description' => 'payment for item #4564',
            ],
            'success_url' => 'https://successURL',
            'cancel_url' => 'https://cancelSuccessURL',
            'error_url' => 'https://errorSuccessURL',
            'message' => 'thank you for using our service',
        ];

        $responseBody = [
            'amount' => '0.8',
            'checkout_url' => 'https://checkouts.addispay.et/get-orders',
            'isCommissioned' => false,
            'status' => 'Data received successfully',
            'uuid' => '09782519-0a1f-4b90-aac2-d408515418a5'
        ];

        // Set expectation on ApiClient's post method
        $apiClientMock = $this->addisPay->apiClient;
        $apiClientMock->shouldReceive('post')
            ->once()
            ->with('/api/v1/receive-data', Mockery::on(function ($data) use ($paymentData) {
                // Optional: Add assertions on the structure of $data
                return isset($data['data']['currency']) &&
                       isset($data['data']['first_name']) &&
                       isset($data['data']['email']);
            }))
            ->andReturn($responseBody);

        $response = $this->addisPay->createPayment($paymentData);

        $this->assertEquals('https://checkouts.addispay.et/get-orders', $response['checkout_url']);
        $this->assertEquals('09782519-0a1f-4b90-aac2-d408515418a5', $response['uuid']);
        $this->assertEquals('0.8', $response['amount']);
        $this->assertEquals('Data received successfully', $response['status']);
        $this->assertFalse($response['isCommissioned']);
    }

    public function testCreatePaymentFailure()
    {
        $this->expectException(AddisPayException::class);
        $this->expectExceptionMessage("Invalid response from AddisPay API.");

        $paymentData = [
            'total_amount' => 100,
            'tx_ref' => 'transaction_reference',
            'currency' => 'ETB',
            'first_name' => 'Tilahun',
            'last_name' => 'Feyissa',
            'email' => 'sample@gmail.com',
            'phone_number' => '900000000',
            'session_expired' => 5000,
            'nonce' => 'uniqueID',
            'order_detail' => [
                'items' => 'rfid',
                'description' => 'payment for item #4564',
            ],
            'success_url' => 'https://successURL',
            'cancel_url' => 'https://cancelSuccessURL',
            'error_url' => 'https://errorSuccessURL',
            'message' => 'thank you for using our service',
        ];

        // Set expectation on ApiClient's post method to return invalid response
        $apiClientMock = $this->addisPay->apiClient;
        $apiClientMock->shouldReceive('post')
            ->once()
            ->with('/api/v1/receive-data', Mockery::any())
            ->andReturn(['invalid_key' => 'no_url']);

        $this->addisPay->createPayment($paymentData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
