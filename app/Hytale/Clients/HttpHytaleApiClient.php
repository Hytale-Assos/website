<?php

namespace App\Hytale\Clients;

use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\Module;
use App\Hytale\Data\Page;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Data\WhitelistEntry;
use App\Hytale\Exceptions\HytaleApiException;
use App\Hytale\Support\ApiSigner;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Real transport: talks to the Hytale core API over HTTP.
 *
 * @see docs/api.md
 */
final class HttpHytaleApiClient implements HytaleApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ApiSigner $signer,
        private readonly int $timeout = 10,
    ) {}

    public function health(): array
    {
        $response = $this->request()->get('/health');

        /** @var array{status: string, database: string} $payload */
        $payload = $this->decodeObject($response, '/health');

        return $payload;
    }

    public function servers(array $query = []): Page
    {
        $response = $this->request()->get('/api/v1/servers', $query);

        return Page::fromArray(
            $this->decodeObject($response, '/api/v1/servers'),
            HytaleServer::fromArray(...),
        );
    }

    public function server(string $id): HytaleServer
    {
        $endpoint = '/api/v1/servers/'.$id;

        return HytaleServer::fromArray(
            $this->decodeObject($this->request()->get($endpoint), $endpoint),
        );
    }

    public function createServer(string $moduleId, string $name, string $url): HytaleServer
    {
        $endpoint = '/api/v1/servers';

        $response = $this->signer->signWrite($this->request(), [
            'module_id' => $moduleId,
            'name' => $name,
            'url' => $url,
        ])->post($endpoint);

        return HytaleServer::fromArray($this->decodeObject($response, $endpoint));
    }

    public function deleteServer(string $id): void
    {
        $endpoint = '/api/v1/servers/'.$id;

        $this->ensureSuccess(
            $this->signer->signWrite($this->request())->delete($endpoint),
            $endpoint,
        );
    }

    public function sessions(array $query = []): Page
    {
        $response = $this->request()->get('/api/v1/sessions', $query);

        return Page::fromArray(
            $this->decodeObject($response, '/api/v1/sessions'),
            PlayerSession::fromArray(...),
        );
    }

    public function playerSessions(string $hytaleId, array $query = []): Page
    {
        $endpoint = '/api/v1/players/'.$hytaleId.'/sessions';

        return Page::fromArray(
            $this->decodeObject($this->request()->get($endpoint, $query), $endpoint),
            PlayerSession::fromArray(...),
        );
    }

    public function whitelists(array $query = []): Page
    {
        $response = $this->request()->get('/api/v1/whitelists', $query);

        return Page::fromArray(
            $this->decodeObject($response, '/api/v1/whitelists'),
            WhitelistEntry::fromArray(...),
        );
    }

    public function playerWhitelists(string $hytaleId, array $query = []): Page
    {
        $endpoint = '/api/v1/players/'.$hytaleId.'/whitelists';

        return Page::fromArray(
            $this->decodeObject($this->request()->get($endpoint, $query), $endpoint),
            WhitelistEntry::fromArray(...),
        );
    }

    public function addToWhitelist(string $hytaleServerId, string $hytaleId): WhitelistEntry
    {
        $endpoint = '/api/v1/whitelists';

        $response = $this->signer->signWrite($this->request(), [
            'hytale_server_id' => $hytaleServerId,
            'hytale_id' => $hytaleId,
        ])->post($endpoint);

        return WhitelistEntry::fromArray($this->decodeObject($response, $endpoint));
    }

    public function removeFromWhitelist(string $id): void
    {
        $endpoint = '/api/v1/whitelists/'.$id;

        $this->ensureSuccess(
            $this->signer->signWrite($this->request())->delete($endpoint),
            $endpoint,
        );
    }

    public function modules(): array
    {
        $endpoint = '/api/v1/modules';

        /** @var array<int, array<string, mixed>> $payload */
        $payload = $this->decodeList($this->request()->get($endpoint), $endpoint);

        return array_map(Module::fromArray(...), $payload);
    }

    public function module(string $id): Module
    {
        $endpoint = '/api/v1/modules/'.$id;

        return Module::fromArray($this->decodeObject($this->request()->get($endpoint), $endpoint));
    }

    private function request(): PendingRequest
    {
        return $this->signer
            ->signRead(Http::baseUrl($this->baseUrl)->timeout($this->timeout)->acceptJson())
            ->asJson();
    }

    /**
     * Decode an object payload (single resource).
     *
     * @return array<string, mixed>
     */
    private function decodeObject(Response $response, string $endpoint): array
    {
        $this->ensureSuccess($response, $endpoint);

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        return $payload;
    }

    /**
     * Decode a list payload (array of resources).
     *
     * @return array<int, array<string, mixed>>
     */
    private function decodeList(Response $response, string $endpoint): array
    {
        $this->ensureSuccess($response, $endpoint);

        /** @var array<int, array<string, mixed>> $payload */
        $payload = array_values((array) ($response->json() ?? []));

        return $payload;
    }

    private function ensureSuccess(Response $response, string $endpoint): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error');

        throw HytaleApiException::fromResponse(
            $response->status(),
            is_string($message) ? $message : null,
            $endpoint,
        );
    }
}
