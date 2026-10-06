<?php

namespace App\Hytale\Support;

use App\Hytale\Exceptions\HytaleApiException;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Coerces raw Hytale API payload values into typed ones.
 *
 * The core is an external system, so its responses are validated
 * defensively instead of trusted: an unparsable optional value degrades
 * to null, while a missing required identifier is a malformed payload
 * and raises a typed {@see HytaleApiException} so callers surface their
 * standard error state instead of crashing.
 */
final class ApiPayloads
{
    /**
     * Parse a timestamp from the core; unparsable values degrade to null.
     */
    public static function toCarbon(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Read a required non-empty string; a missing one means the core
     * returned a malformed resource.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw HytaleApiException::malformed(sprintf('missing or empty "%s"', $key));
        }

        return $value;
    }
}
