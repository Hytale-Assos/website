<?php

namespace App\Hytale;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\HytaleFeed;
use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\Page;
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

    /**
     * Upper bound on the pages fetched per collection: 25 pages of 200,
     * far beyond any plausible member data, so an absurd core total turns
     * into the standard error instead of an unbounded request storm.
     */
    private const MAX_PAGES = 25;

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
            $servers = $this->allItems(
                fn (array $query) => $this->client->servers($query),
                static fn (HytaleServer $server): array => $server->toArray(),
            );

            if ($hytaleId !== null && $hytaleId !== '') {
                $whitelists = $this->allItems(
                    fn (array $query) => $this->client->playerWhitelists($hytaleId, $query),
                    static fn (WhitelistEntry $entry): array => $entry->toArray(),
                );

                $sessions = $this->allItems(
                    fn (array $query) => $this->client->playerSessions($hytaleId, $query),
                    static fn (PlayerSession $session): array => $session->toArray(),
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
     * Fetch every item of a paged collection, bounded by {@see MAX_PAGES}.
     *
     * The advertised `total` drives the loop but is not trusted: iteration
     * stops as soon as the core stops returning items, and the page cap
     * turns an absurd total into the standard error instead of an
     * unbounded request storm.
     *
     * @template T of object
     *
     * @param  callable(array{limit: int, offset: int}): Page<T>  $fetch
     * @param  callable(T): array<string, mixed>  $toArray
     * @return array<int, array<string, mixed>>
     */
    private function allItems(callable $fetch, callable $toArray): array
    {
        $items = [];
        $offset = 0;
        $pages = 0;

        do {
            $page = $fetch(['limit' => self::PAGE_SIZE, 'offset' => $offset]);

            foreach ($page->items as $item) {
                $items[] = $toArray($item);
            }

            $offset += self::PAGE_SIZE;
            $pages++;

            if ($pages >= self::MAX_PAGES && $offset < $page->total) {
                throw HytaleApiException::malformed(
                    sprintf('more than %d pages advertised', self::MAX_PAGES)
                );
            }
        } while ($offset < $page->total && $page->items !== []);

        return $items;
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
