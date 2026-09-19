<?php

namespace App\Hytale\Data;

use Illuminate\Support\Carbon;

/**
 * `PlayerSessionView` (docs/api.md).
 */
final readonly class PlayerSession
{
    public function __construct(
        public string $id,
        public string $hytaleServerId,
        public string $hytaleId,
        public ?Carbon $joinedAt = null,
        public ?Carbon $endedAt = null,
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
            hytaleServerId: (string) ($payload['hytale_server_id'] ?? ''),
            hytaleId: (string) ($payload['hytale_id'] ?? ''),
            joinedAt: self::toDate($payload['joined_at'] ?? null),
            endedAt: self::toDate($payload['ended_at'] ?? null),
            createdAt: self::toDate($payload['created_at'] ?? null),
            updatedAt: self::toDate($payload['updated_at'] ?? null),
        );
    }

    public function isOpen(): bool
    {
        return $this->endedAt === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hytale_server_id' => $this->hytaleServerId,
            'hytale_id' => $this->hytaleId,
            'joined_at' => $this->joinedAt?->toIso8601ZuluString(),
            'ended_at' => $this->endedAt?->toIso8601ZuluString(),
            'created_at' => $this->createdAt?->toIso8601ZuluString(),
            'updated_at' => $this->updatedAt?->toIso8601ZuluString(),
        ];
    }

    private static function toDate(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}
