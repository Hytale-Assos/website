<?php

it('sends the full security header set on every response', function (string $method, string $url) {
    $response = $this->{$method}($url);

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Permissions-Policy'))->not->toBeEmpty()
        ->and($response->headers->get('Cross-Origin-Opener-Policy'))->toBe('same-origin')
        ->and($response->headers->get('Content-Security-Policy'))->not->toBeEmpty();
})->with([
    'inertia page' => ['get', '/login'],
    'json probe' => ['get', '/up'],
    'unknown path' => ['get', '/this-page-does-not-exist'],
]);

it('announces the page script nonce in the content security policy', function () {
    $response = $this->get('/login');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'nonce-");

    preg_match("/'nonce-([^']+)'/", $csp, $matches);

    expect($matches)->not->toBeEmpty();
    expect($response->getContent())->toContain('nonce="'.$matches[1].'"');
});

it('serves the health probe as minimal json with the security headers', function () {
    $response = $this->get('/up');

    $response->assertOk()->assertJson(['status' => 'ok']);

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    $content = (string) $response->getContent();

    expect($content)
        ->not->toContain('<html')
        ->not->toContain('cdn.jsdelivr.net')
        ->not->toContain('fonts.bunny.net');
});

it('sends hsts behind a trusted https proxy', function () {
    config(['trustedproxy.proxies' => '*']);

    $https = $this->get('/login', ['X-Forwarded-Proto' => 'https']);

    expect($https->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
});

it('sends no hsts over plain http', function () {
    expect($this->get('/login')->headers->get('Strict-Transport-Security'))->toBeNull();
});

it('sends no content security policy in the local environment', function () {
    $this->app->instance('env', 'local');

    expect($this->get('/login')->headers->get('Content-Security-Policy'))->toBeNull();

    $this->app->instance('env', 'testing');
});
