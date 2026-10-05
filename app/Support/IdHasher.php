<?php

namespace App\Support;

/**
 * Deterministic blind-index hash of a non-email identifier (Hytale id,
 * Discord id). Single source of truth for the scheme: the encrypted
 * casts write it into the companion *_hash columns and every lookup or
 * uniqueness check computes it through this class, so the writer and
 * the readers can never drift apart. Changing the scheme happens here
 * and nowhere else.
 */
class IdHasher
{
    /**
     * Compute the deterministic hash of the identifier.
     */
    public static function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
