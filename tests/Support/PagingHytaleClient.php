<?php

namespace Tests\Support;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\Page;
use Closure;
use Mockery;

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
