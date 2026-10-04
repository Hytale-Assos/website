<?php

namespace App\Casts;

use App\Support\EmailHasher;

/**
 * Same encryption pattern as EncryptedWithHash, but the companion hash
 * column carries a keyed HMAC of the normalized email instead of a plain
 * SHA-256: emails are enumerable, so the hash must not be computable
 * offline from a database dump alone.
 *
 * @see EncryptedWithHash
 */
class EncryptedEmailWithHash extends EncryptedWithHash
{
    /**
     * Compute the deterministic hash of the email address.
     */
    protected function hashValue(string $value): string
    {
        return EmailHasher::hash($value);
    }
}
