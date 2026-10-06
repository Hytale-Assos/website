<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\Page;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Points\PointsCalculator;
use App\Hytale\Points\PointsService;
use Carbon\CarbonImmutable;

/**
 * A HytaleApiClient double that answers playerSessions() from a closure and
 * throws for every other contract method (they are never invoked).
 *
 * @param  Closure(int): array{0: array<int, PlayerSession>, 1: int}  $pageAt
 *                                                                             Receives the requested offset, returns [items, advertised total].
 */
function pagingClient(Closure $pageAt): PagingHytaleClient
{
    return new PagingHytaleClient($pageAt);
}

class PagingHytaleClient
{
    public int $playerSessionsCalls = 0;

    public readonly HytaleApiClient $client;

    public function __construct(private readonly Closure $pageAt)
    {
        $this->client = Mockery::mock(HytaleApiClient::class);
        $this->client->shouldReceive('playerSessions')->andReturnUsing(
            fn (string $hytaleId, array $query = []) => $this->page($query)
        );
    }

    /**
     * @param  array{limit?: int, offset?: int}  $query
     */
    private function page(array $query): Page
    {
        $this->playerSessionsCalls++;

        $offset = (int) ($query['offset'] ?? 0);

        [$items, $total] = ($this->pageAt)($offset);

        return new Page($items, $total, (int) ($query['limit'] ?? 200), $offset);
    }
}

function oneHourSession(int $index): PlayerSession
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

function paginationService(HytaleApiClient $client): PointsService
{
    return new PointsService(
        $client,
        new PointsCalculator(
            hoursThreshold: 2.0,
            pointsPerReward: 0.5,
            weekStartsOn: CarbonImmutable::MONDAY,
            maxSessionPoints: 2.0,
        ),
    );
}

it('sums the hours of every session across all pages', function () {
    $total = 450;

    $client = pagingClient(function (int $offset) use ($total): array {
        $start = intdiv($offset, 200) * 200;

        if ($start >= $total) {
            return [[], $total];
        }

        $items = [];
        $count = min(200, $total - $start);
        for ($i = 0; $i < $count; $i++) {
            $items[] = oneHourSession($start + $i);
        }

        return [$items, $total];
    });

    $result = paginationService($client->client)->forMember('player-1');

    expect($result['error'])->toBeNull()
        ->and($result['points']['hoursTotal'])->toBe(450.0);
});

it('stops at the first empty page and ignores a far larger advertised total', function () {
    $client = pagingClient(function (int $offset): array {
        $items = $offset === 0
            ? [oneHourSession(0), oneHourSession(1), oneHourSession(2), oneHourSession(3)]
            : [];

        return [$items, 100000];
    });

    $result = paginationService($client->client)->forMember('player-1');

    expect($result['error'])->toBeNull()
        ->and($result['points']['hoursTotal'])->toBe(4.0)
        ->and($client->playerSessionsCalls)->toBeLessThan(10);
});

it('errors out after a bounded number of pages instead of trusting an absurd total', function () {
    $client = pagingClient(function (int $offset): array {
        $page = intdiv($offset, 200);

        $items = [];
        for ($i = 0; $i < 200; $i++) {
            $items[] = oneHourSession($page * 200 + $i);
        }

        return [$items, 1000000000];
    });

    $result = paginationService($client->client)->forMember('player-1');

    expect($result['error'])->toBeString()
        ->and($result['points']['points'])->toEqual(0.0)
        ->and($result['points']['hoursTotal'])->toEqual(0.0)
        ->and($client->playerSessionsCalls)->toBeGreaterThanOrEqual(20)
        ->and($client->playerSessionsCalls)->toBeLessThanOrEqual(26);
});
