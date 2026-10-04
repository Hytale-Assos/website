<?php

namespace App\Hytale\Clients;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\Module;
use App\Hytale\Data\Page;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Data\WhitelistEntry;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Caches read responses to limit calls to the core.
 *
 * GET payloads are stored as arrays (objects are not cacheable with
 * `serializable_classes => false`). Every write bumps a version key, which
 * instantly invalidates all cached reads. The store (Redis) and the TTL are
 * configured in config/hytale.php.
 */
final class CachingHytaleApiClient implements HytaleApiClient
{
    public function __construct(
        private readonly HytaleApiClient $client,
        private readonly CacheRepository $cache,
        private readonly int $ttl = 60,
        private readonly string $prefix = 'hytale-api',
    ) {}

    public function health(): array
    {
        /** @var array{status: string, database: string} $payload */
        $payload = $this->remember('health', fn (): array => $this->client->health());

        return $payload;
    }

    public function servers(array $query = []): Page
    {
        return $this->rememberPage(
            'servers',
            $query,
            fn (): Page => $this->client->servers($query),
            HytaleServer::fromArray(...),
        );
    }

    public function server(string $id): HytaleServer
    {
        return HytaleServer::fromArray($this->remember(
            'server',
            fn (): array => $this->client->server($id)->toArray(),
            [$id],
        ));
    }

    public function createServer(string $moduleId, string $name, string $url): HytaleServer
    {
        $server = $this->client->createServer($moduleId, $name, $url);
        $this->invalidate();

        return $server;
    }

    public function deleteServer(string $id): void
    {
        $this->client->deleteServer($id);
        $this->invalidate();
    }

    public function sessions(array $query = []): Page
    {
        return $this->rememberPage(
            'sessions',
            $query,
            fn (): Page => $this->client->sessions($query),
            PlayerSession::fromArray(...),
        );
    }

    public function playerSessions(string $hytaleId, array $query = []): Page
    {
        return $this->rememberPage(
            'player-sessions',
            $query,
            fn (): Page => $this->client->playerSessions($hytaleId, $query),
            PlayerSession::fromArray(...),
            [$hytaleId],
        );
    }

    public function whitelists(array $query = []): Page
    {
        return $this->rememberPage(
            'whitelists',
            $query,
            fn (): Page => $this->client->whitelists($query),
            WhitelistEntry::fromArray(...),
        );
    }

    public function playerWhitelists(string $hytaleId, array $query = []): Page
    {
        return $this->rememberPage(
            'player-whitelists',
            $query,
            fn (): Page => $this->client->playerWhitelists($hytaleId, $query),
            WhitelistEntry::fromArray(...),
            [$hytaleId],
        );
    }

    public function addToWhitelist(string $hytaleServerId, string $hytaleId): WhitelistEntry
    {
        $entry = $this->client->addToWhitelist($hytaleServerId, $hytaleId);
        $this->invalidate();

        return $entry;
    }

    public function removeFromWhitelist(string $id): void
    {
        $this->client->removeFromWhitelist($id);
        $this->invalidate();
    }

    public function modules(): array
    {
        /** @var array<int, array<string, mixed>> $payload */
        $payload = $this->remember(
            'modules',
            fn (): array => array_map(
                static fn (Module $module): array => $module->toArray(),
                $this->client->modules(),
            ),
        );

        return array_map(Module::fromArray(...), $payload);
    }

    public function module(string $id): Module
    {
        return Module::fromArray($this->remember(
            'module',
            fn (): array => $this->client->module($id)->toArray(),
            [$id],
        ));
    }

    /**
     * @template TCacheValue of array<mixed>
     *
     * @param  \Closure(): TCacheValue  $resolver
     * @param  array<int, string>  $extra
     * @return TCacheValue
     */
    private function remember(string $method, \Closure $resolver, array $extra = []): array
    {
        /** @var TCacheValue $payload */
        $payload = $this->cache->remember($this->key($method, $extra), $this->ttl, $resolver);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  \Closure(): Page<mixed>  $resolver
     * @param  callable(array<string, mixed>): mixed  $mapper
     * @param  array<int, string>  $extra
     * @return Page<mixed>
     */
    private function rememberPage(
        string $method,
        array $query,
        \Closure $resolver,
        callable $mapper,
        array $extra = [],
    ): Page {
        /** @var array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int} $payload */
        $payload = $this->remember(
            $method,
            fn (): array => $resolver()->toArray(),
            [...$extra, ...$this->hashQuery($query)],
        );

        return Page::fromArray($payload, $mapper);
    }

    /**
     * @param  array<int, string>  $extra
     */
    private function key(string $method, array $extra): string
    {
        $version = (int) $this->cache->get($this->versionKey(), 1);

        return implode(':', [$this->prefix, 'v'.$version, $method, ...$extra]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, string>
     */
    private function hashQuery(array $query): array
    {
        if ($query === []) {
            return [];
        }

        return [md5(json_encode($query) ?: '')];
    }

    private function invalidate(): void
    {
        $version = (int) $this->cache->get($this->versionKey(), 1);

        $this->cache->forever($this->versionKey(), $version + 1);
    }

    private function versionKey(): string
    {
        return $this->prefix.':version';
    }
}
