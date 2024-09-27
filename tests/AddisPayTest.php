<?php

namespace AddisPay\AddisPaySDK\Tests;

use Orchestra\Testbench\TestCase;
use AddisPay\AddisPaySDK\Providers\AddisPayServiceProvider;
use AddisPay\AddisPaySDK\Facades\AddisPay;
use AddisPay\AddisPaySDK\Exceptions\AddisPayException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class AddisPayTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [AddisPayServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'AddisPay' => AddisPay::class,
        ];
    }

    /** @test */
    public function it_can_initiate_payment()
    {
        // Mock the Guzzle client
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('post')
             ->once()
             ->andReturn(new Response(200, [], json_encode(['payment_url' => 'https://payment.url'])));

        $this->app->instance(Client::class, $mock);

        $transactionDetail = [
            'total_amount' => "100",
            'tx_ref' => "2226787667",
            'currency' => "ETB",
            'first_name' => "abebe",
            'email' => "abebe@gmail.com",
            'phone_number' => "+251921309013",
            'last_name' => "kebede",
            'session_expired' => "5",
            'nonce' => now()->toIso8601String(),
            'order_detail' => [
                'items' => "test item",
                'description' => "I am testing this",
            ],
        ];

        $paymentUrl = AddisPay::payNow($transactionDetail, 'public_key');

        $this->assertEquals('https://payment.url', $paymentUrl);
    }

    /** @test */
    public function it_throws_exception_on_invalid_response()
    {
        // Mock the Guzzle client
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('post')
             ->once()
             ->andReturn(new Response(200, [], json_encode(['invalid_key' => 'no_url'])));

        $this->app->instance(Client::class, $mock);

        $transactionDetail = [
            'total_amount' => "100",
            'tx_ref' => "2226787667",
            'currency' => "ETB",
            'first_name' => "abebe",
            'email' => "abebe@gmail.com",
            'phone_number' => "+251921309013",
            'last_name' => "kebede",
            'session_expired' => "5",
            'nonce' => now()->toIso8601String(),
            'order_detail' => [
                'items' => "test item",
                'description' => "I am testing this",
            ],
        ];

        $this->expectException(AddisPayException::class);
        $this->expectExceptionMessage('Invalid response from AddisPay.');

        AddisPay::payNow($transactionDetail, 'public_key');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
