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
     * Fetch every session for a member, page by page.
     *
     * @return array<int, array{hytale_server_id?: mixed, joined_at?: mixed, ended_at?: mixed}>
     */
    private function allSessions(string $hytaleId): array
    {
        $sessions = [];
        $offset = 0;

        do {
            $page = $this->client->playerSessions($hytaleId, [
                'limit' => self::PAGE_SIZE,
                'offset' => $offset,
            ]);

            foreach ($page->items as $session) {
                $sessions[] = $session->toArray();
            }

            $offset += self::PAGE_SIZE;
        } while ($offset < $page->total);

        return $sessions;
    }
}
