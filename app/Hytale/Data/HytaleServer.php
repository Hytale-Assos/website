<?php

namespace App\Hytale\Data;

use App\Hytale\Support\ApiPayloads;
use Illuminate\Support\Carbon;

/**
 * `HytaleServerView` (docs/api.md).
 */
final readonly class HytaleServer
{
    public function __construct(
        public string $id,
        public string $moduleId,
        public string $name,
        public string $url,
        public ?Carbon $createdAt = null,
        public ?Carbon $updatedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: ApiPayloads::requiredString($payload, 'id'),
            moduleId: (string) ($payload['module_id'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            url: self::toUrl($payload['url'] ?? ''),
            createdAt: ApiPayloads::toCarbon($payload['created_at'] ?? null),
            updatedAt: ApiPayloads::toCarbon($payload['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->moduleId,
            'name' => $this->name,
            'url' => $this->url,
            'created_at' => $this->createdAt?->toIso8601ZuluString(),
            'updated_at' => $this->updatedAt?->toIso8601ZuluString(),
        ];
    }

    /**
     * A server url is a join address (host or host:port) with no scheme, but
     * the core may also hand back an http(s) URL. Either is kept; any other
     * scheme (javascript:, data:, ...) is neutralized to an empty string so
     * it can never become a clickable link.
     */
    private static function toUrl(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return $scheme === null || in_array($scheme, ['http', 'https'], true) ? $value : '';
    }
}
