<?php

use App\Providers\HytaleServiceProvider;
use ReflectionProperty;

it('does not boot in production with the mock client enabled', function () {
    $this->app->instance('env', 'production');
    config()->set('hytale.mock', true);

    // The guard targets the HTTP runtime: console commands such as
    // package discovery must be able to boot in production.
    (new ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);

    expect(fn () => $this->app->register(HytaleServiceProvider::class, force: true))
        ->toThrow(RuntimeException::class, 'HYTALE_API_MOCK');

    $this->app->instance('env', 'testing');
});

it('does not boot in production with missing core credentials when the mock is disabled', function (string $missingKey) {
    $this->app->instance('env', 'production');
    config()->set([
        'hytale.mock' => false,
        'hytale.api_key' => 'test-api-key',
        'hytale.hmac_secret' => 'test-hmac-secret',
    ]);
    config()->set($missingKey, null);

    (new ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);

    expect(fn () => $this->app->register(HytaleServiceProvider::class, force: true))
        ->toThrow(RuntimeException::class, 'HYTALE_API_KEY and HYTALE_API_HMAC_SECRET');

    $this->app->instance('env', 'testing');
})->with([
    'missing api key' => ['hytale.api_key'],
    'missing hmac secret' => ['hytale.hmac_secret'],
]);

it('boots in production with the mock disabled and the core credentials provided', function () {
    $this->app->instance('env', 'production');
    config()->set([
        'hytale.mock' => false,
        'hytale.api_key' => 'test-api-key',
        'hytale.hmac_secret' => 'test-hmac-secret',
    ]);

    (new ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);

    $this->app->register(HytaleServiceProvider::class, force: true);

    $this->app->instance('env', 'testing');

    expect(true)->toBeTrue();
});

it('boots in a production console context even with the mock enabled', function () {
    $this->app->instance('env', 'production');
    config()->set('hytale.mock', true);

    // Pest runs in the console: leave the runtime flag untouched.
    // Build tooling (package discovery, migrations) must boot without
    // the core credentials.
    $this->app->register(HytaleServiceProvider::class, force: true);

    $this->app->instance('env', 'testing');

    expect(true)->toBeTrue();
});

it('boots outside production with the mock enabled and no credentials', function () {
    config()->set([
        'hytale.mock' => true,
        'hytale.api_key' => null,
        'hytale.hmac_secret' => null,
    ]);

    (new ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);

    $this->app->register(HytaleServiceProvider::class, force: true);

    expect(true)->toBeTrue();
});
