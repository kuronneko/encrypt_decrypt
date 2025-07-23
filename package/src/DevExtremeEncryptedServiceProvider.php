<?php

namespace Kuronneko\LaravelDevExtremeEncrypted;

use Illuminate\Support\ServiceProvider;

class DevExtremeEncryptedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register any services here
        $this->mergeConfigFrom(
            __DIR__.'/../config/devextreme-encrypted.php', 'devextreme-encrypted'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/devextreme-encrypted.php' => config_path('devextreme-encrypted.php'),
        ], 'config');

        // Publish traits (optional - they can be used directly)
        $this->publishes([
            __DIR__.'/Traits' => app_path('Traits/DevExtremeEncrypted'),
        ], 'traits');

        // Register console commands if any
        if ($this->app->runningInConsole()) {
            // Register commands here if needed
        }
    }
}
