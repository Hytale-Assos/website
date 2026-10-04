<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Exceptions\HytaleApiException;
use App\Hytale\Support\ApiSigner;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('hytale.mock', false);
    config()->set('hytale.base_url', 'http://core.test');
    config()->set('hytale.api_key', 'test-api-key');
    config()->set('hytale.hmac_secret', 'test-hmac-secret');
    config()->set('hytale.cache.enabled', false);

    app()->forgetInstance(HytaleApiClient::class);
});

$serverPayload = [
    'id' => '01a0a967-cbaa-7746-b9e8-b16daee6d50b',
    'module_id' => '01a0a967-cb39-76c1-a30a-4983d3633604',
    'name' => 'survie',
    'url' => 'https://hytale.example/survie',
    'created_at' => '2026-09-16T08:49:06Z',
    'updated_at' => '2026-09-16T08:49:06Z',
];

it('sends the api key on read requests', function () use ($serverPayload) {
    Http::fake([
        'core.test/api/v1/servers' => Http::response([
            'items' => [$serverPayload],
            'total' => 1,
            'limit' => 50,
            'offset' => 0,
        ]),
    ]);

    $page = app(HytaleApiClient::class)->servers();

    expect($page->total)->toBe(1)
        ->and($page->items[0]->name)->toBe('survie')
        ->and($page->items[0]->id)->toBe($serverPayload['id']);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'test-api-key')
        && ! $request->hasHeader('X-Signature'));
});

it('signs write requests with a valid hmac signature', function () use ($serverPayload) {
    Http::fake([
        'core.test/api/v1/whitelists' => Http::response([
            'id' => '01a0a967-f45f-77f6-a7d9-c7622bfc5e2c',
            'hytale_server_id' => $serverPayload['id'],
            'hytale_id' => '11111111-1111-7111-8111-111111111111',
            'created_at' => '2026-09-16T08:49:17Z',
            'updated_at' => '2026-09-16T08:49:17Z',
        ], 201),
    ]);

    $entry = app(HytaleApiClient::class)->addToWhitelist(
        $serverPayload['id'],
        '11111111-1111-7111-8111-111111111111',
    );

    expect($entry->hytaleServerId)->toBe($serverPayload['id']);

    $signer = new ApiSigner('test-api-key', 'test-hmac-secret');

    Http::assertSent(function ($request) use ($signer) {
        $timestamp = $request->header('X-Timestamp')[0] ?? '';
        $body = $request->body();

        return $request->hasHeader('X-Api-Key', 'test-api-key')
            && $request->hasHeader('X-Signature', $signer->signature($timestamp, $body));
    });
});

it('signs delete requests with an empty body', function () {
    Http::fake([
        'core.test/api/v1/whitelists/*' => Http::response(null, 204),
    ]);

    app(HytaleApiClient::class)->removeFromWhitelist('entry-1');

    $signer = new ApiSigner('test-api-key', 'test-hmac-secret');

    Http::assertSent(function ($request) use ($signer) {
        $timestamp = $request->header('X-Timestamp')[0] ?? '';

        return $request->method() === 'DELETE'
            && $request->hasHeader('X-Signature', $signer->signature($timestamp, ''))
            && $request->body() === '';
    });
});

it('throws a typed exception on api errors', function () {
    Http::fake([
        'core.test/api/v1/servers/*' => Http::response(['error' => 'server not found'], 404),
    ]);

    app(HytaleApiClient::class)->server('missing');
})->throws(HytaleApiException::class, 'server not found');

it('normalizes a connection failure into an unreachable exception', function () {
    Http::fake([
        'core.test/api/v1/servers*' => fn () => throw new ConnectionException('cURL error 7'),
    ]);

    try {
        app(HytaleApiClient::class)->servers();
        $this->fail('A connection failure should not be swallowed.');
    } catch (HytaleApiException $exception) {
        expect($exception->isUnavailable())->toBeTrue()
            ->and($exception->status)->toBe(HytaleApiException::UNAVAILABLE);
    }
});

it('maps a paginated response', function () use ($serverPayload) {
    Http::fake([
        'core.test/api/v1/servers*' => Http::response([
            'items' => [$serverPayload],
            'total' => 42,
            'limit' => 1,
            'offset' => 0,
        ]),
    ]);

    $page = app(HytaleApiClient::class)->servers(['limit' => 1]);

    expect($page->total)->toBe(42)
        ->and($page->limit)->toBe(1)
        ->and($page->items)->toHaveCount(1);
});
