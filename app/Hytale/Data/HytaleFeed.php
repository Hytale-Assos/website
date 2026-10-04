<?php

namespace App\Hytale\Data;

/**
 * A bundle of data loaded from the Hytale core for a member page.
 *
 * Any API failure is captured in `$error` instead of being thrown, so pages
 * can render a friendly message while still showing whatever succeeded.
 */
final readonly class HytaleFeed
{
    /**
     * @param  array<int, array<string, mixed>>  $servers
     * @param  array<int, array<string, mixed>>  $whitelists
     * @param  array<int, array<string, mixed>>  $sessions
     */
    public function __construct(
        public array $servers = [],
        public array $whitelists = [],
        public array $sessions = [],
        public ?string $error = null,
    ) {}

    /**
     * @return array{servers: array<int, array<string, mixed>>, whitelists: array<int, array<string, mixed>>, sessions: array<int, array<string, mixed>>, error: string|null}
     */
    public function toArray(): array
    {
        return [
            'servers' => $this->servers,
            'whitelists' => $this->whitelists,
            'sessions' => $this->sessions,
            'error' => $this->error,
        ];
    }
}
