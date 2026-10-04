<?php

use App\Hytale\Clients\FakeHytaleApiClient;
use App\Hytale\Exceptions\HytaleApiException;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::store()->clear();

    $this->client = new FakeHytaleApiClient(
        cache: Cache::store(),
        moduleId: '01a0a967-cb40-76c1-a30a-4983d3633605',
    );
});

it('lists the seeded servers', function () {
    $page = $this->client->servers();

    expect($page->total)->toBe(2)
        ->and($page->items)->toHaveCount(2)
        ->and($page->items[0]->name)->toBe('survie');
});

it('filters servers by module', function () {
    $page = $this->client->servers(['module_id' => '01a0a967-cb39-76c1-a30a-4983d3633604']);

    expect($page->total)->toBe(2);
});

it('paginates results', function () {
    $page = $this->client->servers(['limit' => 1, 'offset' => 0]);

    expect($page->items)->toHaveCount(1)
        ->and($page->limit)->toBe(1)
        ->and($page->total)->toBe(2);
});

it('adds an entry to the whitelist idempotently', function () {
    $server = $this->client->servers()->items[0];
    $hytaleId = '22222222-2222-7222-8222-222222222222';

    $first = $this->client->addToWhitelist($server->id, $hytaleId);
    $second = $this->client->addToWhitelist($server->id, $hytaleId);

    expect($second->id)->toBe($first->id)
        ->and($this->client->playerWhitelists($hytaleId)->total)->toBe(1);
});

it('removes an entry from the whitelist', function () {
    $server = $this->client->servers()->items[0];
    $entry = $this->client->addToWhitelist($server->id, '33333333-3333-7333-8333-333333333333');

    $this->client->removeFromWhitelist($entry->id);

    expect($this->client->playerWhitelists('33333333-3333-7333-8333-333333333333')->total)->toBe(0);
});

it('throws when the whitelist entry does not exist', function () {
    $this->client->removeFromWhitelist('unknown');
})->throws(HytaleApiException::class);

it('throws when the server does not exist', function () {
    $this->client->server('unknown');
})->throws(HytaleApiException::class);

it('reports health and modules', function () {
    expect($this->client->health())->toBe(['status' => 'ok', 'database' => 'up'])
        ->and($this->client->modules())->not->toBeEmpty()
        ->and($this->client->module($this->client->modules()[0]->id)->name)->toBe('plugin-hytale');
});
