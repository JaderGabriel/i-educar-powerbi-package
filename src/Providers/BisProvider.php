<?php

namespace iEducar\Packages\Bis\Providers;

use Illuminate\Support\ServiceProvider;

class BisProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__ . '/../../config/bis.php',
            key: 'bis'
        );

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'bis');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        $this->publishes([
            __DIR__ . '/../../resources/assets/css/bi-print.css' => public_path('css/bi-print.css'),
        ], 'bis-assets');
    }
}
