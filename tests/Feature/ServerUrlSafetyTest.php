<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a server registered with a normal url keeps its url intact in the servers page props', function (string $url) {
    $user = User::factory()->create();

    app(HytaleApiClient::class)->createServer(config('hytale.module_id'), 'Safe Server', $url);

    $response = $this
        ->actingAs($user)
        ->get(route('servers.index'));

    $props = [];

    $response->assertInertia(function (Assert $page) use (&$props) {
        $props = data_get($page->toArray(), 'props');

        return $page
            ->component('Servers')
            ->has('servers');
    });

    $server = collect($props['servers'])->firstWhere('name', 'Safe Server');

    expect($server)->not->toBeNull();
    expect($server['url'])->toBe($url);
})->with([
    'http url' => ['http://play.example.com'],
    'https url' => ['https://play.example.com/server/1'],
]);

test('a server registered with a dangerous scheme url appears with an empty url in the servers page props', function (string $dangerousUrl) {
    $user = User::factory()->create();

    app(HytaleApiClient::class)->createServer(config('hytale.module_id'), 'Dangerous Server', $dangerousUrl);

    $response = $this
        ->actingAs($user)
        ->get(route('servers.index'));

    $props = [];

    $response->assertInertia(function (Assert $page) use (&$props) {
        $props = data_get($page->toArray(), 'props');

        return $page
            ->component('Servers')
            ->has('servers');
    });

    $server = collect($props['servers'])->firstWhere('name', 'Dangerous Server');

    expect($server)->not->toBeNull();
    expect($server['url'])->toBe('');
    expect(json_encode($props))->not->toContain($dangerousUrl);
})->with([
    'javascript scheme' => ['javascript:alert(1)'],
    'data uri' => ['data:text/html;base64,SGVsbG8gd29ybGQ='],
]);

test('the servers page still renders successfully when a server carries a dangerous url', function () {
    $user = User::factory()->create();

    $client = app(HytaleApiClient::class);
    $client->createServer(config('hytale.module_id'), 'Safe Server', 'https://play.example.com');
    $client->createServer(config('hytale.module_id'), 'Dangerous Server', 'javascript:alert(1)');

    $response = $this
        ->actingAs($user)
        ->get(route('servers.index'));

    $response->assertOk();

    $props = [];

    $response->assertInertia(function (Assert $page) use (&$props) {
        $props = data_get($page->toArray(), 'props');

        return $page
            ->component('Servers')
            ->has('servers');
    });

    $servers = collect($props['servers']);

    $safe = $servers->firstWhere('name', 'Safe Server');
    $dangerous = $servers->firstWhere('name', 'Dangerous Server');

    expect($safe)->not->toBeNull();
    expect($safe['url'])->toBe('https://play.example.com');
    expect($dangerous)->not->toBeNull();
    expect($dangerous['url'])->toBe('');
});

test('a server registered with an empty url shows an empty url in the servers page props without breaking the page', function () {
    $user = User::factory()->create();

    app(HytaleApiClient::class)->createServer(config('hytale.module_id'), 'No Url Server', '');

    $response = $this
        ->actingAs($user)
        ->get(route('servers.index'));

    $response->assertOk();

    $props = [];

    $response->assertInertia(function (Assert $page) use (&$props) {
        $props = data_get($page->toArray(), 'props');

        return $page
            ->component('Servers')
            ->has('servers');
    });

    $server = collect($props['servers'])->firstWhere('name', 'No Url Server');

    expect($server)->not->toBeNull();
    expect($server['url'])->toBe('');
});
