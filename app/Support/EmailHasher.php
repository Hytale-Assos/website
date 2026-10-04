<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Deterministic, keyed hash of an email address. Used to look users up and
 * enforce email uniqueness without storing emails in clear: the email column
 * is encrypted (not queryable), while this HMAC acts as a blind index.
 *
 * A keyed hash is preferred over a plain SHA-256 because emails are
 * enumerable: an attacker holding only a database dump cannot test candidate
 * emails offline without the application key.
 */
class EmailHasher
{
    /**
     * Compute the deterministic hash of the given email. The email is
     * normalized so that lookups are case- and whitespace-insensitive.
     */
    public static function hash(string $email): string
    {
        return hash_hmac('sha256', Str::lower(trim($email)), Config::get('app.key'));
    }
}
