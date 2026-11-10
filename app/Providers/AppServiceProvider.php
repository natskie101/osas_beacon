<?php

namespace App\Providers;

use App\Services\NvidiaService;
use App\Support\Request as PortalRequest;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NvidiaService::class, fn () => new NvidiaService());

        $this->app->bind(PortalRequest::class, function () {
            return PortalRequest::capture();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Defence in depth: never render debug stack traces in production,
        // even if APP_DEBUG=true was left in the deployed .env.
        if ($this->app->environment('production') && config('app.debug')) {
            config(['app.debug' => false]);
            report(new \RuntimeException('APP_DEBUG=true was ignored because APP_ENV=production.'));
        }
    }
}
