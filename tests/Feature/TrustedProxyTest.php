<?php

it('uses forwarded headers to generate absolute urls when all proxies are trusted', function () {
    config()->set('trustedproxy.proxies', '*');

    $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'example.org',
    ])->assertRedirect('https://example.org/login');
});

it('ignores forwarded headers to generate absolute urls when no proxies are trusted', function () {
    config()->set('trustedproxy.proxies', null);

    $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'example.org',
    ])->assertRedirect('http://localhost/login');
});
