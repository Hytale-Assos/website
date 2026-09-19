<?php

namespace App\Hytale;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\HytaleFeed;
use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Data\WhitelistEntry;
use App\Hytale\Exceptions\HytaleApiException;

/**
 * Loads the data each member page needs from the Hytale core.
 *
 * Errors are swallowed into {@see HytaleFeed::$error} so a page can still
 * render its shell and show a friendly message.
 */
final class HytaleData
{
    public function __construct(
        private readonly HytaleApiClient $client,
    ) {}

    public function forMember(?string $hytaleId): HytaleFeed
    {
        $servers = [];
        $whitelists = [];
        $sessions = [];
        $error = null;

        try {
            $servers = array_map(
                static fn (HytaleServer $server): array => $server->toArray(),
                $this->client->servers()->items,
            );

            if ($hytaleId !== null && $hytaleId !== '') {
                $whitelists = array_map(
                    static fn (WhitelistEntry $entry): array => $entry->toArray(),
                    $this->client->playerWhitelists($hytaleId)->items,
                );

                $sessions = array_map(
                    static fn (PlayerSession $session): array => $session->toArray(),
                    $this->client->playerSessions($hytaleId)->items,
                );
            }
        } catch (HytaleApiException $exception) {
            $error = $exception->getMessage();
        }

        return new HytaleFeed(
            servers: $servers,
            whitelists: $whitelists,
            sessions: $sessions,
            error: $error,
        );
    }
}
