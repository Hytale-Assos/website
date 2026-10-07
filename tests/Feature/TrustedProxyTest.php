<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;

it('uses forwarded headers to generate absolute urls when all proxies are trusted', function () {
    config()->set('trustedproxy.proxies', '*');

    $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'example.org',
    ])->assertRedirect('https://example.org/login');
});

it('ignores forwarded headers to generate absolute urls when no proxies are trusted', function () {
    config()->set('trustedproxy.proxies', null);

    $expected = route('login');

    $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'example.org',
    ])->assertRedirect($expected);
});

it('reports the forwarded client ip when the peer is a trusted proxy', function () {
    config()->set('trustedproxy.proxies', '10.89.3.0/24');

    Route::get('/_test/client-ip', fn (Request $request) => response($request->ip()));

    $this->withServerVariables(['REMOTE_ADDR' => '10.89.3.3'])
        ->get('/_test/client-ip', ['X-Forwarded-For' => '203.0.113.7'])
        ->assertContent('203.0.113.7');
});

it('ignores the forwarded client ip when the peer is not a trusted proxy', function () {
    config()->set('trustedproxy.proxies', null);

    Route::get('/_test/client-ip', fn (Request $request) => response($request->ip()));

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/_test/client-ip', ['X-Forwarded-For' => '198.51.100.1'])
        ->assertContent('203.0.113.9');
});

it('resolves a relative discord redirect against the configured base url', function () {
    $base = 'https://example.org';

    config()->set('app.url', $base);
    config()->set('services.discord', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect' => '/auth/discord/callback',
    ]);
    URL::forceRootUrl($base);
    URL::forceScheme('https');

    $url = Socialite::driver('discord')->stateless()->redirect()->getTargetUrl();
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query['redirect_uri'])->toBe($base.'/auth/discord/callback');
});

it('uses an absolute discord redirect url unchanged', function () {
    $absolute = 'https://accounts.example.com/discord/callback';

    config()->set('services.discord', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect' => $absolute,
    ]);

    $url = Socialite::driver('discord')->stateless()->redirect()->getTargetUrl();
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query['redirect_uri'])->toBe($absolute);
});
