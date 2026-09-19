<?php

namespace App\Hytale\Exceptions;

use RuntimeException;

/**
 * Raised when the Hytale core API returns an error.
 *
 * Mirrors the status codes documented in docs/api.md:
 * 400 invalid body, 401 auth, 403 scope/ownership, 404 not found,
 * 409 conflict, 500 internal.
 */
class HytaleApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?string $endpoint = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(int $status, ?string $message, ?string $endpoint = null): self
    {
        return new self(
            status: $status,
            message: $message ?: 'Hytale API returned status '.$status,
            endpoint: $endpoint,
        );
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }

    public function isConflict(): bool
    {
        return $this->status === 409;
    }

    public function isUnauthorized(): bool
    {
        return $this->status === 401;
    }

    public function isForbidden(): bool
    {
        return $this->status === 403;
    }
}
