<?php

namespace BluedotBd\LaravelBulksms;

use Illuminate\Support\ServiceProvider;

class LaravelBulksmsServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-bulksms.php', 'laravel-bulksms');

        // One instance per resolution: the builder keeps the recipient, message
        // and lines, so a shared singleton would leak them between calls.
        $this->app->bind(LaravelBulksms::class, function () {
            return new LaravelBulksms(config('laravel-bulksms.config'));
        });

        // Kept for backwards compatibility with `app('laravel-bulksms')`.
        $this->app->alias(LaravelBulksms::class, 'laravel-bulksms');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-bulksms.php' => config_path('laravel-bulksms.php'),
            ], 'laravel-bulksms-config');
        }
    }
}
