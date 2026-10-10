<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\Page;
use App\Hytale\Data\PlayerSession;
use App\Hytale\HytaleData;
use Carbon\CarbonImmutable;
use Mockery;

/**
 * Build a HytaleApiClient double that answers sessions(), whitelists() and
 * servers() from per-page closures. Every method receives the request offset
 * and must return [items, advertised total].
 *
 * Returns [client, callCounters].
 */
function memberClient(
    Closure $sessionsAt,
    ?Closure $serversAt = null,
    ?Closure $whitelistsAt = null,
): array {
    $serversAt ??= fn (int $offset): array => [[], 0];
    $whitelistsAt ??= fn (int $offset): array => [[], 0];

    $counters = ['servers' => 0, 'whitelists' => 0, 'sessions' => 0];

    $client = Mockery::mock(HytaleApiClient::class);

    $client->shouldReceive('servers')->andReturnUsing(
        function (array $query = []) use (&$counters, $serversAt): Page {
            $counters['servers']++;

            return pagingPage($serversAt, $query);
        }
    );

    $client->shouldReceive('playerWhitelists')->andReturnUsing(
        function (string $hytaleId, array $query = []) use (&$counters, $whitelistsAt): Page {
            $counters['whitelists']++;

            return pagingPage($whitelistsAt, $query);
        }
    );

    $client->shouldReceive('playerSessions')->andReturnUsing(
        function (string $hytaleId, array $query = []) use (&$counters, $sessionsAt): Page {
            $counters['sessions']++;

            return pagingPage($sessionsAt, $query);
        }
    );

    return [$client, $counters];
}

/**
 * @param  array{limit?: int, offset?: int}  $query
 */
function pagingPage(Closure $pageAt, array $query): Page
{
    $offset = (int) ($query['offset'] ?? 0);

    [$items, $total] = $pageAt($offset);

    return new Page($items, $total, (int) ($query['limit'] ?? 200), $offset);
}

function memberSession(int $index): PlayerSession
{
    $joinedAt = CarbonImmutable::parse('2026-01-01T10:00:00Z')->addHours($index);

    return PlayerSession::fromArray([
        'id' => sprintf('session-%d', $index),
        'hytale_server_id' => 'server-1',
        'hytale_id' => 'player-1',
        'joined_at' => $joinedAt->format('Y-m-d\TH:i:s\Z'),
        'ended_at' => $joinedAt->addHour()->format('Y-m-d\TH:i:s\Z'),
    ]);
}

it('returns every session across all pages', function () {
    $total = 250;

    [$client] = memberClient(function (int $offset) use ($total): array {
        $start = intdiv($offset, 200) * 200;

        if ($start >= $total) {
            return [[], $total];
        }

        $items = [];
        $count = min(200, $total - $start);
        for ($i = 0; $i < $count; $i++) {
            $items[] = memberSession($start + $i);
        }

        return [$items, $total];
    });

    $result = (new HytaleData($client))->forMember('player-1');

    expect($result->error)->toBeNull()
        ->and($result->sessions)->toHaveCount(250);
});

it('stops at the first empty page and ignores a far larger advertised total', function () {
    [$client, $counters] = memberClient(function (int $offset): array {
        $items = $offset === 0
            ? array_map(fn (int $i) => memberSession($i), range(0, 199))
            : [];

        return [$items, 100000];
    });

    $result = (new HytaleData($client))->forMember('player-1');

    expect($result->error)->toBeNull()
        ->and($result->sessions)->toHaveCount(200)
        ->and($counters['sessions'])->toBeLessThan(10);
});

it('errors out after a bounded number of pages instead of trusting an absurd total', function () {
    [$client, $counters] = memberClient(function (int $offset): array {
        $page = intdiv($offset, 200);

        $items = [];
        for ($i = 0; $i < 200; $i++) {
            $items[] = memberSession($page * 200 + $i);
        }

        return [$items, 1000000000];
    });

    $result = (new HytaleData($client))->forMember('player-1');

    expect($result->error)->not->toBeNull()
        ->and($counters['sessions'])->toBeLessThan(100);
});

it('loads servers, whitelists and sessions without error when everything is fine', function () {
    [$client] = memberClient(function (int $offset): array {
        $items = $offset === 0
            ? [memberSession(0), memberSession(1), memberSession(2)]
            : [];

        return [$items, 3];
    });

    $result = (new HytaleData($client))->forMember('player-1');

    expect($result->error)->toBeNull()
        ->and($result->servers)->toBeArray()
        ->and($result->whitelists)->toBeArray()
        ->and($result->sessions)->toHaveCount(3);
});
