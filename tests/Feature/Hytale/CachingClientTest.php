<?php

use App\Hytale\Clients\CachingHytaleApiClient;
use App\Hytale\Contracts\HytaleApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('hytale.mock', false);
    config()->set('hytale.base_url', 'http://core.test');
    config()->set('hytale.api_key', 'test-api-key');
    config()->set('hytale.hmac_secret', 'test-hmac-secret');
    config()->set('hytale.cache.enabled', true);
    config()->set('hytale.cache.store', 'array');
    config()->set('hytale.cache.ttl', 60);

    Cache::store('array')->clear();

    app()->forgetInstance(HytaleApiClient::class);
});

it('wraps the http client in a cache decorator', function () {
    expect(app(HytaleApiClient::class))->toBeInstanceOf(CachingHytaleApiClient::class);
});

it('serves repeated reads from cache', function () {
    Http::fake([
        'core.test/api/v1/servers' => Http::response([
            'items' => [[
                'id' => 'server-1',
                'module_id' => 'module-1',
                'name' => 'survie',
                'url' => 'https://hytale.example/survie',
            ]],
            'total' => 1,
            'limit' => 50,
            'offset' => 0,
        ]),
    ]);

    $client = app(HytaleApiClient::class);

    $client->servers();
    $client->servers();

    Http::assertSentCount(1);
});

it('invalidates cached reads after a write', function () {
    Http::fake([
        'core.test/api/v1/servers' => Http::response([
            'items' => [],
            'total' => 0,
            'limit' => 50,
            'offset' => 0,
        ]),
        'core.test/api/v1/whitelists' => Http::response([
            'id' => 'entry-1',
            'hytale_server_id' => 'server-1',
            'hytale_id' => 'player-1',
        ], 201),
    ]);

    $client = app(HytaleApiClient::class);

    $client->servers();
    $client->addToWhitelist('server-1', 'player-1');
    $client->servers();

    Http::assertSentCount(3);
});
