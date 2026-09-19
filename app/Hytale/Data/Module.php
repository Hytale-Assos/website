<?php

namespace App\Hytale\Data;

use Illuminate\Support\Carbon;

/**
 * `ModuleView` (docs/api.md).
 */
final readonly class Module
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public bool $isEnabled,
        public array $scopes,
        public ?Carbon $createdAt = null,
        public ?Carbon $updatedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var array<int, string> $scopes */
        $scopes = is_array($payload['scopes'] ?? null) ? array_values($payload['scopes']) : [];

        return new self(
            id: (string) $payload['id'],
            name: (string) ($payload['name'] ?? ''),
            description: isset($payload['description']) ? (string) $payload['description'] : null,
            isEnabled: (bool) ($payload['is_enabled'] ?? false),
            scopes: $scopes,
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
            'name' => $this->name,
            'description' => $this->description,
            'is_enabled' => $this->isEnabled,
            'scopes' => $this->scopes,
            'created_at' => $this->createdAt?->toIso8601ZuluString(),
            'updated_at' => $this->updatedAt?->toIso8601ZuluString(),
        ];
    }

    private static function toDate(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}
