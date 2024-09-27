<?php

namespace AddisPay\AddisPaySDK\Providers;

use Illuminate\Support\ServiceProvider;
use AddisPay\AddisPaySDK\AddisPay;

class AddisPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('addispay.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'addispay'
        );

        // Bind the AddisPay class
        $this->app->singleton('addispay', function ($app) {
            return new AddisPay($app['config']->get('addispay'));
        });
    }
}
