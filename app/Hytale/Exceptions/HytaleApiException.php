<?php

namespace App\Hytale\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Raised when the Hytale core API returns an error.
 *
 * Mirrors the status codes documented in docs/api.md:
 * 400 invalid body, 401 auth, 403 scope/ownership, 404 not found,
 * 409 conflict, 500 internal.
 *
 * Status `0` is used when the core could not be reached at all (connection
 * refused, DNS failure, timeout).
 */
class HytaleApiException extends RuntimeException
{
    public const UNAVAILABLE = 0;

    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?string $endpoint = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function fromResponse(int $status, ?string $message, ?string $endpoint = null): self
    {
        return new self(
            status: $status,
            message: $message ?: 'Hytale API returned status '.$status,
            endpoint: $endpoint,
        );
    }

    /**
     * The core could not be reached (network error, timeout).
     */
    public static function unavailable(?string $endpoint = null, ?Throwable $previous = null): self
    {
        return new self(
            status: self::UNAVAILABLE,
            message: 'Hytale API is unreachable',
            endpoint: $endpoint,
            previous: $previous,
        );
    }

    /**
     * The core answered but its payload is malformed (missing or empty
     * required fields, or values beyond any plausible bound) and cannot
     * be turned into a typed resource.
     */
    public static function malformed(string $detail): self
    {
        return new self(
            status: 500,
            message: 'Malformed Hytale API payload: '.$detail,
        );
    }

    public function isUnavailable(): bool
    {
        return $this->status === self::UNAVAILABLE;
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
