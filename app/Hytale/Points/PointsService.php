<?php

namespace App\Hytale\Points;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Exceptions\HytaleApiException;
use Carbon\CarbonImmutable;

/**
 * Loads a member's sessions and turns them into "Points Open".
 */
final class PointsService
{
    private const PAGE_SIZE = 200;

    /**
     * Upper bound on the pages fetched per member: 25 pages of 200, far
     * beyond any plausible play history, so an absurd core total turns
     * into the standard error instead of an unbounded request storm.
     */
    private const MAX_PAGES = 25;

    public function __construct(
        private readonly HytaleApiClient $client,
        private readonly PointsCalculator $calculator,
    ) {}

    /**
     * @return array{points: array<string, mixed>, error: string|null}
     */
    public function forMember(?string $hytaleId): array
    {
        if ($hytaleId === null || $hytaleId === '') {
            return ['points' => $this->calculator->compute([])->toArray(), 'error' => null];
        }

        try {
            $sessions = $this->allSessions($hytaleId);
        } catch (HytaleApiException $exception) {
            return [
                'points' => $this->calculator->compute([])->toArray(),
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'points' => $this->calculator->compute($sessions, CarbonImmutable::now())->toArray(),
            'error' => null,
        ];
    }

    /**
     * Fetch a member's sessions, page by page.
     *
     * The advertised `total` drives the loop but is not trusted:
     * iteration stops as soon as the core stops returning items, and a
     * hard page cap turns an absurd total into the standard error
     * instead of an unbounded request storm.
     *
     * @return array<int, array{hytale_server_id?: mixed, joined_at?: mixed, ended_at?: mixed}>
     */
    private function allSessions(string $hytaleId): array
    {
        $sessions = [];
        $offset = 0;
        $pages = 0;

        do {
            $page = $this->client->playerSessions($hytaleId, [
                'limit' => self::PAGE_SIZE,
                'offset' => $offset,
            ]);

            foreach ($page->items as $session) {
                $sessions[] = $session->toArray();
            }

            $offset += count($page->items);
            $pages++;

            if ($pages >= self::MAX_PAGES && $offset < $page->total) {
                throw HytaleApiException::malformed(
                    sprintf('more than %d sessions advertised', self::MAX_PAGES * self::PAGE_SIZE)
                );
            }
        } while ($offset < $page->total && $page->items !== []);

        return $sessions;
    }
}
