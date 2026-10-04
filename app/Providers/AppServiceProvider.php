<?php

namespace App\Providers;

use App\Auth\HashedEloquentUserProvider;
use App\Auth\Passwords\HashedEmailPasswordBrokerManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Discord\Provider as DiscordProvider;
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
        });

        $this->configureDefaults();
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
