<?php

namespace AshenafiPixel\AddisPaySDK\Providers;

use Illuminate\Support\ServiceProvider;
use AshenafiPixel\AddisPaySDK\AddisPay;

class AddisPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Bind the AddisPay class to the service container
        $this->app->singleton('addispay', function ($app) {
            return new AddisPay();
        });

        // Merge package configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'addispay');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('addispay.php'),
        ], 'config');
    }
}
