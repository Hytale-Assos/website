<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Deterministic, keyed hash of a non-email identifier (Hytale id, Discord
 * id), used as the blind index of the encrypted *_hash columns. Single
 * source of truth for the scheme: the encrypted casts write it and every
 * lookup or uniqueness check computes it through this class, so the
 * writer and the readers can never drift apart. Changing the scheme
 * happens here and nowhere else.
 *
 * A keyed hash is preferred over a plain SHA-256 for the same reason as
 * EmailHasher: Discord ids are snowflakes, numeric and enumerable, so an
 * attacker holding only a database dump cannot test candidate ids
 * offline without the application key. Hytale ids are UUIDs and not
 * enumerable, but one scheme for every blind index avoids a future
 * mistake where a new column picks the wrong one.
 */
class IdHasher
{
    /**
     * Compute the deterministic keyed hash of the identifier.
     */
    public static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) Config::get('app.key'));
    }
}
