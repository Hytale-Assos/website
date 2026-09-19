<?php

namespace App\Hytale\Clients;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\Module;
use App\Hytale\Data\Page;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Data\WhitelistEntry;
use App\Hytale\Exceptions\HytaleApiException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mock transport used while the real API is not deployed.
 *
 * Enabled with `HYTALE_API_MOCK=true`. It answers the same contract as
 * {@see HttpHytaleApiClient} with deterministic data and keeps writes in the
 * cache so the effect of adding/removing a whitelist entry survives between
 * requests. No network call is made. Flip the env flag to use the real API.
 */
final class FakeHytaleApiClient implements HytaleApiClient
{
    private const STATE_KEY = 'hytale-api-mock-state';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $moduleId,
        private readonly string $moduleName = 'siteweb',
    ) {}

    public function health(): array
    {
        return ['status' => 'ok', 'database' => 'up'];
    }

    public function servers(array $query = []): Page
    {
        $servers = $this->state()['servers'];

        if (is_string($query['module_id'] ?? null)) {
            $servers = array_values(array_filter(
                $servers,
                static fn (array $server): bool => $server['module_id'] === $query['module_id'],
            ));
        }

        return $this->paginate($servers, $query, HytaleServer::fromArray(...));
    }

    public function server(string $id): HytaleServer
    {
        return HytaleServer::fromArray($this->findServer($id));
    }

    public function createServer(string $moduleId, string $name, string $url): HytaleServer
    {
        $state = $this->state();

        foreach ($state['servers'] as $server) {
            if ($server['module_id'] === $moduleId && $server['name'] === $name) {
                throw new HytaleApiException(409, 'name already taken');
            }
        }

        $now = now()->toIso8601ZuluString();

        $state['servers'][] = [
            'id' => (string) Str::uuid7(),
            'module_id' => $moduleId,
            'name' => $name,
            'url' => $url,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->save($state);

        return HytaleServer::fromArray($state['servers'][array_key_last($state['servers'])]);
    }

    public function deleteServer(string $id): void
    {
        $state = $this->state();

        $state['servers'] = array_values(array_filter(
            $state['servers'],
            static fn (array $server): bool => $server['id'] !== $id,
        ));

        $this->save($state);
    }

    public function sessions(array $query = []): Page
    {
        $sessions = $this->state()['sessions'];

        $sessions = $this->filterBy($sessions, $query, ['hytale_server_id', 'hytale_id']);

        return $this->paginate($sessions, $query, PlayerSession::fromArray(...));
    }

    public function playerSessions(string $hytaleId, array $query = []): Page
    {
        $query['hytale_id'] = $hytaleId;

        return $this->sessions($query);
    }

    public function whitelists(array $query = []): Page
    {
        $entries = $this->state()['whitelists'];

        $entries = $this->filterBy($entries, $query, ['hytale_server_id', 'hytale_id']);

        return $this->paginate($entries, $query, WhitelistEntry::fromArray(...));
    }

    public function playerWhitelists(string $hytaleId, array $query = []): Page
    {
        $query['hytale_id'] = $hytaleId;

        return $this->whitelists($query);
    }

    public function addToWhitelist(string $hytaleServerId, string $hytaleId): WhitelistEntry
    {
        $state = $this->state();

        $this->findServer($hytaleServerId);

        foreach ($state['whitelists'] as $entry) {
            if ($entry['hytale_server_id'] === $hytaleServerId && $entry['hytale_id'] === $hytaleId) {
                return WhitelistEntry::fromArray($entry);
            }
        }

        $now = now()->toIso8601ZuluString();

        $entry = [
            'id' => (string) Str::uuid7(),
            'hytale_server_id' => $hytaleServerId,
            'hytale_id' => $hytaleId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $state['whitelists'][] = $entry;
        $this->save($state);

        return WhitelistEntry::fromArray($entry);
    }

    public function removeFromWhitelist(string $id): void
    {
        $state = $this->state();

        $exists = false;

        foreach ($state['whitelists'] as $entry) {
            if ($entry['id'] === $id) {
                $exists = true;
                break;
            }
        }

        if (! $exists) {
            throw new HytaleApiException(404, 'whitelist entry not found');
        }

        $state['whitelists'] = array_values(array_filter(
            $state['whitelists'],
            static fn (array $entry): bool => $entry['id'] !== $id,
        ));

        $this->save($state);
    }

    public function modules(): array
    {
        return array_map(Module::fromArray(...), $this->state()['modules']);
    }

    public function module(string $id): Module
    {
        foreach ($this->state()['modules'] as $module) {
            if ($module['id'] === $id) {
                return Module::fromArray($module);
            }
        }

        throw new HytaleApiException(404, 'module not found');
    }

    /**
     * @return array<string, mixed>
     */
    private function findServer(string $id): array
    {
        foreach ($this->state()['servers'] as $server) {
            if ($server['id'] === $id) {
                return $server;
            }
        }

        throw new HytaleApiException(404, 'server not found');
    }

    /**
     * Filter a list of arrays by scalar query parameters.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $query
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function filterBy(array $items, array $query, array $keys): array
    {
        foreach ($keys as $key) {
            if (! is_string($query[$key] ?? null) || $query[$key] === '') {
                continue;
            }

            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => ($item[$key] ?? null) === $query[$key],
            ));
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $query
     * @param  callable(array<string, mixed>): mixed  $mapper
     * @return Page<mixed>
     */
    private function paginate(array $items, array $query, callable $mapper): Page
    {
        $total = count($items);
        $limit = min(200, max(1, (int) ($query['limit'] ?? 50)));
        $offset = max(0, (int) ($query['offset'] ?? 0));

        $slice = array_slice($items, $offset, $limit);

        return new Page(
            items: array_map($mapper, $slice),
            total: $total,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * Load the mutable mock state, seeding it on first use.
     *
     * @return array{servers: array<int, array<string, mixed>>, sessions: array<int, array<string, mixed>>, whitelists: array<int, array<string, mixed>>, modules: array<int, array<string, mixed>>}
     */
    private function state(): array
    {
        /** @var array{servers: array<int, array<string, mixed>>, sessions: array<int, array<string, mixed>>, whitelists: array<int, array<string, mixed>>, modules: array<int, array<string, mixed>>} $state */
        $state = $this->cache->rememberForever(self::STATE_KEY, fn (): array => $this->seed());

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function save(array $state): void
    {
        $this->cache->forever(self::STATE_KEY, $state);
    }

    /**
     * @return array{servers: array<int, array<string, mixed>>, sessions: array<int, array<string, mixed>>, whitelists: array<int, array<string, mixed>>, modules: array<int, array<string, mixed>>}
     */
    private function seed(): array
    {
        $now = now()->subDays(7)->toIso8601ZuluString();

        $pluginModuleId = '01a0a967-cb39-76c1-a30a-4983d3633604';
        $survivalId = '01a0a967-cbaa-7746-b9e8-b16daee6d50b';
        $creativeId = '01a0a967-cbbb-7746-b9e8-b16daee6d50c';
        $playerId = '11111111-1111-7111-8111-111111111111';

        return [
            'modules' => [
                [
                    'id' => $pluginModuleId,
                    'name' => 'plugin-hytale',
                    'description' => 'game plugin',
                    'is_enabled' => true,
                    'scopes' => ['player_sessions:read', 'player_sessions:write'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => $this->moduleId,
                    'name' => $this->moduleName,
                    'description' => 'website & member portal',
                    'is_enabled' => true,
                    'scopes' => ['hytale_servers:read', 'player_sessions:read', 'whitelist:read', 'whitelist:write'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'servers' => [
                [
                    'id' => $survivalId,
                    'module_id' => $pluginModuleId,
                    'name' => 'survie',
                    'url' => 'https://hytale.example/survie',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => $creativeId,
                    'module_id' => $pluginModuleId,
                    'name' => 'creatif',
                    'url' => 'https://hytale.example/creatif',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'sessions' => [
                [
                    'id' => (string) Str::uuid7(),
                    'hytale_server_id' => $survivalId,
                    'hytale_id' => $playerId,
                    'joined_at' => Carbon::now()->subHours(2)->toIso8601ZuluString(),
                    'ended_at' => Carbon::now()->subHour()->toIso8601ZuluString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'whitelists' => [
                [
                    'id' => (string) Str::uuid7(),
                    'hytale_server_id' => $survivalId,
                    'hytale_id' => $playerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ];
    }
}
