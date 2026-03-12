<?php

namespace iEducar\Packages\Bis\Providers;

use iEducar\Packages\Bis\Http\Middleware\EnsureBiMenu;
use iEducar\Packages\Bis\View\Components\BiPrintWrapper;
use iEducar\Packages\Bis\View\Components\BiPowered;
use Illuminate\Support\Facades\Blade;
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

        // Alias de middleware para manter compatibilidade com o padrão do i-Educar
        $this->app['router']->aliasMiddleware('ieducar.bi.menu', EnsureBiMenu::class);

        // Componentes Blade do BI
        Blade::component('bi-powered', BiPowered::class);
        Blade::component('bi-print-wrapper', BiPrintWrapper::class);

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        $this->publishes([
            __DIR__ . '/../../resources/assets/css/bi-print.css' => public_path('css/bi-print.css'),
            __DIR__ . '/../../resources/assets/css/bi-dashboard.css' => public_path('css/bi-dashboard.css'),
        ], 'bis-assets');
    }
}
