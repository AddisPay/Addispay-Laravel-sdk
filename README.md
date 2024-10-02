# AddisPay Laravel SDK

A comprehensive Laravel SDK for integrating the AddisPay payment gateway into your Laravel applications. This SDK simplifies the process of initiating payments, handling callbacks, and managing secure data transmission with robust encryption mechanisms.

 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Initiate a Payment](#initiate-a-payment)
  - [Handle Callbacks](#handle-callbacks)
- [Example Responses](#example-responses)
  - [HTML Responses](#html-responses)
  - [Raw Postman Responses](#raw-postman-responses)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

 Features

- Seamless Payment Initiation: Easily create and initiate payment orders.
- Secure Data Handling: Encrypt and decrypt sensitive data using RSA encryption.
- Callback Management: Handle success, cancellation, and error callbacks efficiently.
- Robust Error Handling: Manage and report errors gracefully.
- Extensible and Maintainable: Modular structure for easy enhancements and maintenance.

 Installation

You can install the AddisPay SDK via Composer. Ensure that you have Composer installed on your system. If not, follow the [Composer installation guide](https://getcomposer.org/download/).

bash/cmd/cmd 

composer require addispay/addispay-sdk


 Configuration

After installation, you need to publish the configuration file and set up your environment variables.

 1. Publish Configuration

Run the following Artisan command to publish the configuration file:

bash/cmd
php artisan vendor:publish --provider="addispay\AddisPaySDK\Providers\AddisPayServiceProvider" --tag=config


This will create a `config/addispay.php` file in your Laravel application's `config` directory.

 2. Set Environment Variables

In your `.env` file, add the following variables with your AddisPay credentials and endpoints:



ADDISPAY_PUBLIC_API_KEY=your_public_api_key
ADDISPAY_PRIVATE_KEY=your_private_key
ADDISPAY_CHECKOUT_API_URL=https://checkoutapi.addispay.et/api/v1/receive-data
ADDISPAY_RETURN_API_URL=https://yourdomain.com/payment/success
ADDISPAY_CALLBACK_API_URL=https://yourdomain.com/payment/callback


Ensure that these values are correctly set to match your AddisPay account details and your application's URLs.

 Usage

 Initiate a Payment

To initiate a payment, use the `AddisPay` facade provided by the SDK. Below is an example of how to create a payment order in a controller.

 1. Create a Payment Form

First, create a Blade view for your payment form where users can enter payment details.

`resources/views/payment/form.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Make a Payment</title>
</head>
<body>
    <h1>Make a Payment</h1>

    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('payment.initiate') }}" method="POST">
        @csrf
        <label for="amount">Amount:</label>
        <input type="number" name="amount" id="amount" required><br>

        <label for="currency">Currency:</label>
        <input type="text" name="currency" id="currency" value="ETB" required><br>

        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" id="first_name" required><br>

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" id="last_name" required><br>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" id="phone_number" required><br>

        <label for="description">Description:</label>
        <textarea name="description" id="description"></textarea><br>

        <button type="submit">Pay Now</button>
    </form>
</body>
</html>


 2. Define Routes

Add routes for displaying the payment form, initiating the payment, and handling callbacks.

`routes/web.php`

php
use App\Http\Controllers\PaymentController;

Route::get('/payment/form', [PaymentController::class, 'showPaymentForm'])->name('payment.form');
Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/callback', [PaymentController::class, 'paymentCallback'])->name('payment.callback');
Route::get('/payment/error', [PaymentController::class, 'paymentError'])->name('payment.error');


 3. Create the Controller

Create a controller to handle payment initiation and callbacks.

`app/Http/Controllers/PaymentController.php`

php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use addispayiPixel\AddisPaySDK\Facades\AddisPay;

class PaymentController extends Controller
{
    /
     * Show the payment form.
     */
    public function showPaymentForm()
    {
        return view('payment.form');
    }

    /
     * Initiate the payment.
     */
    public function initiatePayment(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // Prepare payment data
        $paymentData = [
            'total_amount' => $validated['amount'],
            'tx_ref' => uniqid(), // or any unique transaction reference
            'currency' => strtoupper($validated['currency']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'session_expired' => 5000, // in seconds
            'nonce' => uniqid(),
            'order_detail' => [
                'items' => 'Product Name', // or get from request
                'description' => $validated['description'] ?? 'Payment Description',
            ],
            'success_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
            'error_url' => route('payment.error'),
            'message' => 'Thank you for using our service',
        ];

        try {
            // Create payment using the SDK
            $response = AddisPay::createPayment($paymentData);

            // Redirect to the checkout URL
            return redirect()->away($response['checkout_url'] . '/' . $response['uuid']);
        } catch (\Exception $e) {
            // Handle errors (e.g., log and display a user-friendly message)
            \Log::error('AddisPay Payment Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Payment initiation failed. Please try again.']);
        }
    }

    /
     * Handle the payment success callback.
     */
    public function paymentSuccess(Request $request)
    {
        // Example: Verify payment details
        $paymentId = $request->input('payment_id');
        $status = $request->input('status');

        try {
            // Optionally, decrypt data if it's encrypted
            // $paymentId = AddisPay::decryptData($paymentId);
            // $status = AddisPay::decryptData($status);

            if ($status === 'success') {
                // Update your order status in the database
                // Notify the user, etc.
                return view('payment.success');
            } else {
                return view('payment.failed')->with('message', 'Payment was not successful.');
            }
        } catch (\Exception $e) {
            \Log::error('AddisPay Callback Error: ' . $e->getMessage());
            return view('payment.error')->with('error', 'There was an error processing your payment.');
        }
    }

    /
     * Handle the payment cancellation callback.
     */
    public function paymentCancel()
    {
        return view('payment.cancel');
    }

    /
     * Handle the payment error callback.
     */
    public function paymentError()
    {
        return view('payment.error');
    }

    /
     * Handle the payment callback (generic).
     */
    public function paymentCallback(Request $request)
    {
        // Handle the callback logic here
        // This method can be used if AddisPay sends a generic callback
        // Instead of specific success, cancel, and error URLs
    }
}


 Handle Callbacks

After a payment is processed, AddisPay will redirect users to the specified `success_url`, `cancel_url`, or `error_url`. Ensure your controller methods handle these callbacks appropriately.

 Step 4: Implement the SDK Classes

Ensure all SDK classes are properly implemented to handle API requests, encryption, and exceptions.

 1. `src/AddisPay.php`

The main SDK class that initializes the API client and handles payment creation.

php
<?php

namespace addispayiPixel\AddisPaySDK;

use addispayiPixel\AddisPaySDK\Exceptions\AddisPayException;

class AddisPay
{
    protected $apiClient;
    protected $publicKey;
    protected $privateKey;

    public function __construct()
    {
        $config = Config::get();
        $this->publicKey = $config['public_api_key'];
        $this->privateKey = $config['private_key'];
        $this->apiClient = new ApiClient($config['checkout_api_url'], $config['public_api_key'], $config['private_key']);
    }

    /
     * Create a payment order.
     *
     * @param array $paymentData
     * @return array
     * @throws AddisPayException
     */
    public function createPayment(array $paymentData)
    {
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

    /
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


 2. `src/Config/config.php`

Configuration settings for the SDK.

php
<?php

namespace addispayiPixel\AddisPaySDK\Config;

class Config
{
    public static function get()
    {
        return [
            'public_api_key' => env('ADDISPAY_PUBLIC_API_KEY', ''),
            'private_key' => env('ADDISPAY_PRIVATE_KEY', ''),
            'checkout_api_url' => env('ADDISPAY_CHECKOUT_API_URL', 'https://checkoutapi.addispay.et/api/v1/receive-data'),
            'return_api_url' => env('ADDISPAY_RETURN_API_URL', 'https://yourdomain.com/payment/success'),
            'callback_api_url' => env('ADDISPAY_CALLBACK_API_URL', 'https://yourdomain.com/payment/callback'),
        ];
    }
}


 3. `src/Facades/AddisPay.php`

Facade for easy access to the SDK's methods.

php
<?php

namespace addispayiPixel\AddisPaySDK\Facades;

use Illuminate\Support\Facades\Facade;

class AddisPay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'addispay';
    }
}


 4. `src/Providers/AddisPayServiceProvider.php`

Service provider to register the SDK in the Laravel application.

php
<?php

namespace addispayiPixel\AddisPaySDK\Providers;

use Illuminate\Support\ServiceProvider;
use addispayiPixel\AddisPaySDK\AddisPay;

class AddisPayServiceProvider extends ServiceProvider
{
    /
     * Register the application services.
     */
    public function register()
    {
        // Bind the AddisPay class to the service container
        $this->app->singleton('addispay', function ($app) {
            return new AddisPay();
        });
    }

    /
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('addispay.php'),
        ], 'config');
    }
}


 5. `src/Exceptions/AddisPayException.php`

Custom exception class for the SDK.

php
<?php

namespace addispayiPixel\AddisPaySDK\Exceptions;

use Exception;

class AddisPayException extends Exception
{
}


 6. `src/ApiClient.php`

Handles HTTP requests to the AddisPay API.

php
<?php

namespace addispayiPixel\AddisPaySDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use addispayiPixel\AddisPaySDK\Exceptions\AddisPayException;

class ApiClient
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;

    public function __construct($apiUrl, $apiKey, $apiSecret)
    {
        $this->client = new Client(['base_uri' => $apiUrl]);
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /
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


 7. `src/Encryption.php`

Utility class for RSA encryption and decryption.

php
<?php

namespace addispayiPixel\AddisPaySDK;

use phpseclib3\Crypt\RSA;
use addispayiPixel\AddisPaySDK\Exceptions\AddisPayException;

class Encryption
{
    /
     * Encrypt data using the provided public key.
     *
     * @param string $data
     * @param string $publicKey
     * @return string
     * @throws AddisPayException
     */
    public static function encrypt($data, $publicKey)
    {
        try {
            $rsa = RSA::loadPublicKey($publicKey);
            $ciphertext = $rsa->encrypt($data);
            return base64_encode($ciphertext);
        } catch (\Exception $e) {
            throw new AddisPayException("Encryption failed: " . $e->getMessage());
        }
    }

    /
     * Decrypt data using the provided private key.
     *
     * @param string $encryptedData
     * @param string $privateKey
     * @return string
     * @throws AddisPayException
     */
    public static function decrypt($encryptedData, $privateKey)
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $plaintext = $rsa->decrypt(base64_decode($encryptedData));
            return $plaintext;
        } catch (\Exception $e) {
            throw new AddisPayException("Decryption failed: " . $e->getMessage());
        }
    }
}


 Example Responses

 HTML Responses

These are the Blade views that are returned to the user based on the payment outcome.

 1. Payment Success

`resources/views/payment/success.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
</head>
<body>
    <h1>Payment Successful</h1>
    <p>Your payment was successful. Thank you!</p>
</body>
</html>


 2. Payment Cancelled

`resources/views/payment/cancel.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
</head>
<body>
    <h1>Payment Cancelled</h1>
    <p>Your payment was cancelled. Please try again.</p>
</body>
</html>


 3. Payment Error

`resources/views/payment/error.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Error</title>
</head>
<body>
    <h1>Payment Error</h1>
    <p>There was an error processing your payment. Please try again.</p>
    @if(session('error'))
        <p>Error Details: {{ session('error') }}</p>
    @endif
</body>
</html>


 Raw Postman Responses

Below are example responses you might receive from the AddisPay API when creating a payment order.

 1. Successful Payment Creation

json
{
    "amount": "100.00",
    "checkout_url": "https://checkouts.addispay.et/get-orders",
    "isCommissioned": false,
    "status": "Data received successfully",
    "uuid": "09782519-0a1f-4b90-aac2-d408515418a5"
}


 2. Failed Payment Creation

json
{
    "error": "Invalid API Key",
    "message": "Authentication failed due to invalid API credentials."
}


 3. Validation Error

json
{
    "error": "Validation Error",
    "message": "The email field is required."
}


 Testing

To ensure the SDK functions correctly, comprehensive tests are included. Follow these steps to run the tests.

 1. Install Development Dependencies

Ensure all development dependencies are installed via Composer.

bash/cmd
composer install


 2. Run PHPUnit Tests

Execute the PHPUnit tests using the following command:

bash/cmd
vendor/bin/phpunit


Note: On Windows, you might need to use `vendor\bin\phpunit` or `vendor/bin/phpunit.exe` depending on your setup.

 3. Sample Test Case

Here's an example of a PHPUnit test case for the SDK.

`tests/AddisPayTest.php`

php
<?php

namespace addispayiPixel\AddisPaySDK\Tests;

use addispayiPixel\AddisPaySDK\AddisPay;
use addispayiPixel\AddisPaySDK\Exceptions\AddisPayException;
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

        // Mock the ApiClient's Guzzle client
        $this->mockClient = Mockery::mock(Client::class);
        
        // Create an instance of AddisPay with mocked ApiClient
        $this->addisPay = new AddisPay();
        $reflection = new \ReflectionClass($this->addisPay);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue($this->addisPay, new \addispayiPixel\AddisPaySDK\ApiClient('https://checkoutapi.addispay.et/api/v1/receive-data', 'your_public_api_key', 'your_private_key'));
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

        // Mock the ApiClient's post method
        $responseBody = [
            'amount' => '0.8',
            'checkout_url' => 'https://checkouts.addispay.et/get-orders',
            'isCommissioned' => false,
            'status' => 'Data received successfully',
            'uuid' => '09782519-0a1f-4b90-aac2-d408515418a5'
        ];

        // Assuming you have a way to mock the ApiClient's post method
        // Here is a simplified example using Mockery

        $mockApiClient = Mockery::mock(\addispayiPixel\AddisPaySDK\ApiClient::class);
        $mockApiClient->shouldReceive('post')
            ->once()
            ->with('/api/v1/receive-data', Mockery::any())
            ->andReturn($responseBody);

        // Inject the mockApiClient into AddisPay
        $reflection = new \ReflectionClass($this->addisPay);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue($this->addisPay, $mockApiClient);

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

        $paymentData = [
            // ... same as above ...
        ];

        // Mock the ApiClient's post method to throw an exception
        $mockApiClient = Mockery::mock(\addispayiPixel\AddisPaySDK\ApiClient::class);
        $mockApiClient->shouldReceive('post')
            ->once()
            ->with('/api/v1/receive-data', Mockery::any())
            ->andThrow(new \Exception('API Error'));

        // Inject the mockApiClient into AddisPay
        $reflection = new \ReflectionClass($this->addisPay);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue($this->addisPay, $mockApiClient);

        $this->addisPay->createPayment($paymentData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}


 Example Usage of the SDK in a Laravel Application

Once your SDK is published on Packagist, merchants can integrate it into their Laravel applications as follows:

 1. Install the SDK via Composer

bash/cmd
composer require addispay/addispay-sdk


 2. Publish Configuration

If your SDK provides configuration files, publish them using Artisan:

bash/cmd
php artisan vendor:publish --provider="addispayiPixel\AddisPaySDK\Providers\AddisPayServiceProvider" --tag=config


This will create a `config/addispay.php` file where merchants can set their API keys and endpoints.

 3. Set Environment Variables

In the `.env` file, add your AddisPay credentials:

env
ADDISPAY_PUBLIC_API_KEY=your_public_api_key
ADDISPAY_PRIVATE_KEY=your_private_key
ADDISPAY_CHECKOUT_API_URL=https://checkoutapi.addispay.et/api/v1/receive-data
ADDISPAY_RETURN_API_URL=https://yourdomain.com/payment/success
ADDISPAY_CALLBACK_API_URL=https://yourdomain.com/payment/callback


 4. Register the Service Provider and Facade (If Not Auto-Discovered)

If your package supports Laravel's auto-discovery, you can skip this step. Otherwise, manually add to `config/app.php`:

php
'providers' => [
    // Other service providers...
    addispayiPixel\AddisPaySDK\Providers\AddisPayServiceProvider::class,
],

'aliases' => [
    // Other aliases...
    'AddisPay' => addispayiPixel\AddisPaySDK\Facades\AddisPay::class,
],


 5. Use the SDK in Controllers

As shown earlier, merchants can use the `AddisPay` facade to create payments and handle callbacks.

Example Controller: `app/Http/Controllers/PaymentController.php`

php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use addispayiPixel\AddisPaySDK\Facades\AddisPay;

class PaymentController extends Controller
{
    /
     * Show the payment form.
     */
    public function showPaymentForm()
    {
        return view('payment.form');
    }

    /
     * Initiate the payment.
     */
    public function initiatePayment(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // Prepare payment data
        $paymentData = [
            'total_amount' => $validated['amount'],
            'tx_ref' => uniqid(), // or any unique transaction reference
            'currency' => strtoupper($validated['currency']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'session_expired' => 5000, // in seconds
            'nonce' => uniqid(),
            'order_detail' => [
                'items' => 'Product Name', // or get from request
                'description' => $validated['description'] ?? 'Payment Description',
            ],
            'success_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
            'error_url' => route('payment.error'),
            'message' => 'Thank you for using our service',
        ];

        try {
            // Create payment using the SDK
            $response = AddisPay::createPayment($paymentData);

            // Redirect to the checkout URL
            return redirect()->away($response['checkout_url'] . '/' . $response['uuid']);
        } catch (\Exception $e) {
            // Handle errors (e.g., log and display a user-friendly message)
            \Log::error('AddisPay Payment Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Payment initiation failed. Please try again.']);
        }
    }

    /
     * Handle the payment success callback.
     */
    public function paymentSuccess(Request $request)
    {
        // Example: Verify payment details
        $paymentId = $request->input('payment_id');
        $status = $request->input('status');

        try {
            // Optionally, decrypt data if it's encrypted
            // $paymentId = AddisPay::decryptData($paymentId);
            // $status = AddisPay::decryptData($status);

            if ($status === 'success') {
                // Update your order status in the database
                // Notify the user, etc.
                return view('payment.success');
            } else {
                return view('payment.failed')->with('message', 'Payment was not successful.');
            }
        } catch (\Exception $e) {
            \Log::error('AddisPay Callback Error: ' . $e->getMessage());
            return view('payment.error')->with('error', 'There was an error processing your payment.');
        }
    }

    /
     * Handle the payment cancellation callback.
     */
    public function paymentCancel()
    {
        return view('payment.cancel');
    }

    /
     * Handle the payment error callback.
     */
    public function paymentError()
    {
        return view('payment.error');
    }
}


 Example Responses

 HTML Responses

These are the Blade views that are returned to the user based on the payment outcome.

 1. Payment Success

`resources/views/payment/success.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
</head>
<body>
    <h1>Payment Successful</h1>
    <p>Your payment was successful. Thank you!</p>
</body>
</html>


 2. Payment Cancelled

`resources/views/payment/cancel.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
</head>
<body>
    <h1>Payment Cancelled</h1>
    <p>Your payment was cancelled. Please try again.</p>
</body>
</html>


 3. Payment Error

`resources/views/payment/error.blade.php`

html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Error</title>
</head>
<body>
    <h1>Payment Error</h1>
    <p>There was an error processing your payment. Please try again.</p>
    @if(session('error'))
        <p>Error Details: {{ session('error') }}</p>
    @endif
</body>
</html>


 Raw Postman Responses

Below are example responses you might receive from the AddisPay API when creating a payment order.

 1. Successful Payment Creation

json
{
    "amount": "100.00",
    "checkout_url": "https://checkouts.addispay.et/get-orders",
    "isCommissioned": false,
    "status": "Data received successfully",
    "uuid": "09782519-0a1f-4b90-aac2-d408515418a5"
}


 2. Failed Payment Creation

json
{
    "error": "Invalid API Key",
    "message": "Authentication failed due to invalid API credentials."
}


 3. Validation Error

json
{
    "error": "Validation Error",
    "message": "The email field is required."
}


 Testing

To ensure the SDK functions correctly, comprehensive tests are included. Follow these steps to run the tests.

 1. Install Development Dependencies

Ensure all development dependencies are installed via Composer.

bash/cmd
composer install


 2. Run PHPUnit Tests

Execute the PHPUnit tests using the following command:

bash/cmd
vendor/bin/phpunit


Note: On Windows, you might need to use `vendor\bin\phpunit` or `vendor/bin/phpunit.exe` depending on your setup.

 3. Sample Test Case

Here's an example of a PHPUnit test case for the SDK.

`tests/AddisPayTest.php`

php
<?php

namespace addispayiPixel\AddisPaySDK\Tests;

use addispayiPixel\AddisPaySDK\AddisPay;
use addispayiPixel\AddisPaySDK\Exceptions\AddisPayException;
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

        // Mock the ApiClient's Guzzle client
        $this->mockClient = Mockery::mock(Client::class);
        
        // Create an instance of AddisPay with mocked ApiClient
        $this->addisPay = new AddisPay();
        $reflection = new \ReflectionClass($this->addisPay);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue($this->addisPay, new \addispayiPixel\AddisPaySDK\ApiClient('https://checkoutapi.addispay.et/api/v1/receive-data', 'your_public_api_key', 'your_private_key'));
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

        // Mock the ApiClient's post method
        $responseBody = [
            'amount' => '0.8',
            'checkout_url' => 'https://checkouts.addispay.et/get-orders',
            'isCommissioned' => false,
            'status' => 'Data received successfully',
            'uuid' => '09782519-0a1f-4b90-aac2-d408515418a5'
        ];

        // Assuming you have a way to mock the ApiClient's post method
        // Here is a simplified example using Mockery

        $mockApiClient = Mockery::mock(\addispayiPixel\AddisPaySDK\ApiClient::class);
        $mockApiClient->shouldReceive('post')
            ->once()
            ->with('/api/v1/receive-data', Mockery::any())
            ->andReturn($responseBody);

        // Inject the mockApiClient into AddisPay
        $reflection = new \ReflectionClass($this->addisPay);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue($this->addisPay, $mockApiClient);

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

        $paymentData = [
            // ... same as above ...
        ];

        // Mock the ApiClient's post method to throw an exception
        $mockApiClient = Mockery::mock(\addispayiPixel\AddisPaySDK\ApiClient::class);
        $mockApiClient->shouldReceive('post')
            ->once()
            ->with('/api/v1/receive-data', Mockery::any())
            ->andThrow(new \Exception('API Error'));

        // Inject the mockApiClient into AddisPay
        $reflection = new \ReflectionClass($this->addisPay);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue($this->addisPay, $mockApiClient);

        $this->addisPay->createPayment($paymentData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}


 Contributing

Contributions are welcome! Please follow these guidelines to contribute to the AddisPay Laravel SDK.

 1. Fork the Repository

Click the Fork button at the top-right corner of the repository page to create a copy of the repository under your GitHub account.

 2. Clone the Forked Repository

bash/cmd
git clone https://github.com/addispay/addispay-sdk.git
cd addispay-sdk


 3. Create a New Branch

Create a new branch for your feature or bug fix.

bash/cmd
git checkout -b feature/add-new-feature


 4. Make Your Changes

Implement your feature or fix the bug in the new branch.

 5. Commit Your Changes

bash/cmd
git add .
git commit -m "Add new feature: Description of the feature"


 6. Push to the Branch

bash/cmd
git push origin feature/add-new-feature


 7. Create a Pull Request

Navigate to the original repository and click on Compare & pull request. Provide a clear description of your changes and submit the pull request.

 License

This project is licensed under the [MIT License](LICENSE).

 Additional Information

 1. Namespace Consistency

Ensure that the namespaces in your PHP files match the `psr-4` autoloading paths defined in `composer.json`. This ensures that classes are autoloaded correctly.

 2. Versioning

Follow [Semantic Versioning](https://semver.org/) to manage your package versions. Use tags like `v1.0.0`, `v1.1.0`, etc., to denote releases.

 3. Security

- Environment Variables: Always use environment variables to store sensitive information like API keys. Do not hardcode them.
  
- Encryption: Ensure that encryption and decryption mechanisms are securely implemented to protect sensitive data.

 4. Continuous Integration (CI)

Consider setting up CI/CD pipelines (e.g., GitHub Actions) to automate testing and deployment processes. This ensures code quality and streamlines the release process.

Example GitHub Actions Workflow: `.github/workflows/ci.yml`

yaml
name: CI

on:
  push:
    branches: [ main ]
    tags: [ 'v*.*.*' ]
  pull_request:
    branches: [ main ]

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Set up PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'

    - name: Install dependencies
      run: composer install --prefer-dist --no-progress --no-suggest

    - name: Run Tests
      run: vendor/bin/phpunit


 5. Troubleshooting

 `'vendor' is not recognized as an internal or external command`

This error typically occurs when the `vendor` directory does not exist because dependencies haven't been installed yet.

  Solution:

1.   Install Dependencies

   Run the following command to install all required dependencies:

   bash/cmd

   composer install
   

2.   Verify Composer Installation

   Ensure that Composer is installed correctly by checking its version:

   bash/cmd 

   composer --version
   

   If Composer is not installed, download and install it from the [Composer website](https://getcomposer.org/download/).

3.   Check `composer.json`

   Ensure that your `composer.json` file is correctly configured. You can validate it using:

   bash/cmd/cmd
   
composer validate
   

 Running PHPUnit on Windows

On Windows, you might need to adjust the PHPUnit command.

 Solution:

Use backslashes or specify the full path to `phpunit.exe` if available.

bash/cmd
vendor\bin\phpunit


Or, if you have PHPUnit installed globally:

bash/cmd

phpunit


 Conclusion

By following the steps outlined above, you can successfully integrate the AddisPay payment gateway into your Laravel applications using the AddisPay Laravel SDK. This SDK abstracts the complexities of API interactions, providing a streamlined and secure method for handling payments.

For any issues or feature requests, please open an issue on the [GitHub repository](https://github.com/addispay/addispay-sdk/issues).

 Happy Coding!
