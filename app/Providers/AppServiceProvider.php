<?php

namespace App\Providers;

use App\Auth\ArrayUserProvider;
use App\Auth\BeaconSessionGuard;
use App\Services\NvidiaService;
use App\Support\Request as PortalRequest;
use Illuminate\Support\Facades\Auth;
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
        // Native auth wiring: an array-backed user provider and a session
        // guard that preserves the portal's beacon_remember cookie scheme and
        // the Active-status login gate (see App\Auth\BeaconSessionGuard).
        Auth::provider('beacon', fn ($app, $config) => new ArrayUserProvider());

        Auth::extend('beacon', function ($app, $name, $config) {
            $guard = new BeaconSessionGuard(
                $name,
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app['session.store'],
                rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
                timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
            );

            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($app['cookie']);
            }

            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($app['events']);
            }

            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            }

            return $guard;
        });

        // Defence in depth: never render debug stack traces in production,
        // even if APP_DEBUG=true was left in the deployed .env.
        if ($this->app->environment('production') && config('app.debug')) {
            config(['app.debug' => false]);
            report(new \RuntimeException('APP_DEBUG=true was ignored because APP_ENV=production.'));
        }
    }
}
