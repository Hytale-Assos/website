<?php

namespace App\Providers;

use App\Auth\HashedEloquentUserProvider;
use App\Auth\Passwords\HashedEmailPasswordBrokerManager;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use SocialiteProviders\Hytale\Provider as HytaleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The users table stores personal data encrypted; the user provider
        // and the password broker therefore resolve users and reset tokens
        // through the deterministic email hash instead of the email column.
        Auth::provider('eloquent-hashed', function ($app, array $config): HashedEloquentUserProvider {
            return new HashedEloquentUserProvider($app['hash'], $config['model']);
        });

        // The default broker manager is registered by a deferred framework
        // provider, so extending the binding is the only registration that
        // survives its lazy registration.
        $this->app->extend('auth.password', fn (): HashedEmailPasswordBrokerManager => new HashedEmailPasswordBrokerManager($this->app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('discord', DiscordProvider::class);
            $event->extendSocialite('hytale', HytaleProvider::class);
        });

        $this->configureRateLimiting();
        $this->configureDefaults();
    }

    /**
     * Named rate limiters for the application's own write endpoints
     * (Fortify configures the authentication ones). Every limiter keys
     * by user id when signed in, by IP otherwise.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('settings', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()->id ?? $request->ip());
        });

        RateLimiter::for('invitations', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id ?? $request->ip());
        });

        RateLimiter::for('whitelist', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id ?? $request->ip());
        });

        RateLimiter::for('data-export', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()->id ?? $request->ip());
        });

        RateLimiter::for('oauth', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id ?? $request->ip());
        });

        RateLimiter::for('password', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()->id ?? $request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
