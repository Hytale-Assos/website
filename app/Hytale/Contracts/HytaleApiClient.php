<?php

namespace App\Hytale\Contracts;

use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\Module;
use App\Hytale\Data\Page;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Data\WhitelistEntry;
use App\Hytale\Exceptions\HytaleApiException;

/**
 * Contract for the Hytale core API, as described in docs/api.md.
 *
 * The website is a module: it authenticates with its own API key and signs
 * writes. This client is the only place that talks to the core; application
 * code depends on this interface, so the real and fake transports are
 * interchangeable.
 */
interface HytaleApiClient
{
    /**
     * GET /health — public.
     *
     * @return array{status: string, database: string}
     *
     * @throws HytaleApiException
     */
    public function health(): array;

    /**
     * GET /api/v1/servers — scope `hytale_servers:read` (global read).
     *
     * @param  array<string, mixed>  $query  module_id, limit, offset
     * @return Page<HytaleServer>
     *
     * @throws HytaleApiException
     */
    public function servers(array $query = []): Page;

    /**
     * GET /api/v1/servers/{id} — scope `hytale_servers:read`.
     *
     * @throws HytaleApiException
     */
    public function server(string $id): HytaleServer;

    /**
     * POST /api/v1/servers — scope `hytale_servers:write`.
     *
     * @throws HytaleApiException
     */
    public function createServer(string $moduleId, string $name, string $url): HytaleServer;

    /**
     * DELETE /api/v1/servers/{id} — scope `hytale_servers:write`.
     *
     * @throws HytaleApiException
     */
    public function deleteServer(string $id): void;

    /**
     * GET /api/v1/sessions — scope `player_sessions:read`.
     *
     * @param  array<string, mixed>  $query  hytale_server_id, hytale_id, limit, offset
     * @return Page<PlayerSession>
     *
     * @throws HytaleApiException
     */
    public function sessions(array $query = []): Page;

    /**
     * GET /api/v1/players/{hytaleId}/sessions — scope `player_sessions:read`.
     *
     * @param  array<string, mixed>  $query  hytale_server_id, limit, offset
     * @return Page<PlayerSession>
     *
     * @throws HytaleApiException
     */
    public function playerSessions(string $hytaleId, array $query = []): Page;

    /**
     * GET /api/v1/whitelists — scope `whitelist:read`.
     *
     * @param  array<string, mixed>  $query  hytale_server_id, hytale_id, limit, offset
     * @return Page<WhitelistEntry>
     *
     * @throws HytaleApiException
     */
    public function whitelists(array $query = []): Page;

    /**
     * GET /api/v1/players/{hytaleId}/whitelists — scope `whitelist:read`.
     *
     * @param  array<string, mixed>  $query  hytale_server_id, limit, offset
     * @return Page<WhitelistEntry>
     *
     * @throws HytaleApiException
     */
    public function playerWhitelists(string $hytaleId, array $query = []): Page;

    /**
     * POST /api/v1/whitelists — scope `whitelist:write` (idempotent).
     *
     * @throws HytaleApiException
     */
    public function addToWhitelist(string $hytaleServerId, string $hytaleId): WhitelistEntry;

    /**
     * DELETE /api/v1/whitelists/{id} — scope `whitelist:write`.
     *
     * @throws HytaleApiException
     */
    public function removeFromWhitelist(string $id): void;

    /**
     * GET /api/v1/modules — scope `modules:read`.
     *
     * @return array<int, Module>
     *
     * @throws HytaleApiException
     */
    public function modules(): array;

    /**
     * GET /api/v1/modules/{id} — scope `modules:read`.
     *
     * @throws HytaleApiException
     */
    public function module(string $id): Module;
}
