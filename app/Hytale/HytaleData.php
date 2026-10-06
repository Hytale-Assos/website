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
    /**
     * Page size used when scanning a member's collection, matching the
     * core's clamped maximum.
     */
    private const PAGE_SIZE = 200;

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

    /**
     * Whether the given whitelist entry belongs to the member: the entry
     * must appear in the member's own whitelist, read page by page.
     *
     * Ownership is the website's responsibility: the core only checks the
     * entry belongs to the module, and the website's API key speaks for
     * every member, so the delete route must prove the entry belongs to
     * the current member before calling the core.
     *
     * @throws HytaleApiException when the core cannot be reached
     */
    public function ownsWhitelistEntry(?string $hytaleId, string $entryId): bool
    {
        if ($hytaleId === null || $hytaleId === '') {
            return false;
        }

        $offset = 0;

        do {
            $page = $this->client->playerWhitelists($hytaleId, [
                'limit' => self::PAGE_SIZE,
                'offset' => $offset,
            ]);

            foreach ($page->items as $entry) {
                if ($entry->id === $entryId) {
                    return true;
                }
            }

            $offset += self::PAGE_SIZE;
        } while ($offset < $page->total && $page->items !== []);

        return false;
    }
}
