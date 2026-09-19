<?php

namespace App\Hytale\Data;

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
            id: (string) $payload['id'],
            moduleId: (string) ($payload['module_id'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            url: (string) ($payload['url'] ?? ''),
            createdAt: self::toDate($payload['created_at'] ?? null),
            updatedAt: self::toDate($payload['updated_at'] ?? null),
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

    private static function toDate(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}
