<?php

namespace App\Hytale\Data;

/**
 * A paginated list as returned by the core API (`Page<T>`).
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param  array<int, T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {}

    /**
     * Build a page from a raw API payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): T  $mapper
     * @return self<T>
     */
    public static function fromArray(array $payload, callable $mapper): self
    {
        /** @var array<int, array<string, mixed>> $rawItems */
        $rawItems = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        return new self(
            items: array_map($mapper, $rawItems),
            total: (int) ($payload['total'] ?? count($rawItems)),
            limit: (int) ($payload['limit'] ?? count($rawItems)),
            offset: (int) ($payload['offset'] ?? 0),
        );
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (object $item): array => method_exists($item, 'toArray') ? $item->toArray() : (array) $item,
                $this->items,
            ),
            'total' => $this->total,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }
}
