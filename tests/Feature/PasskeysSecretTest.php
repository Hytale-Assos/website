<?php

use App\Providers\FortifyServiceProvider;

it('configures a dedicated passkeys user handle secret distinct from the app key', function () {
    $secret = config('fortify.passkeys.user_handle_secret');

    expect($secret)
        ->toBeString()
        ->not->toBe('')
        ->not->toBe(config('app.key'));
});

it('fails with an explicit exception in production when the user handle secret is missing', function () {
    $this->app->instance('env', 'production');
    config()->set('fortify.passkeys.user_handle_secret', null);

    expect(fn () => $this->app->register(FortifyServiceProvider::class, force: true))
        ->toThrow(RuntimeException::class, 'PASSKEYS_USER_HANDLE_SECRET');

    $this->app->instance('env', 'testing');
});

it('does not fail in production when the user handle secret is present', function () {
    $this->app->instance('env', 'production');
    config()->set('fortify.passkeys.user_handle_secret', 'base64:dGVzdGluZy1wYXNza2V5cy11c2VyLWhhbmRsZS1zZWNyZXQ=');

    $this->app->register(FortifyServiceProvider::class, force: true);

    $this->app->instance('env', 'testing');

    expect(true)->toBeTrue();
});
