<?php

namespace App\Auth;

use App\Support\EmailHasher;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Eloquent user provider that resolves credentials against the encrypted
 * users table. Since the email column is encrypted (and therefore not
 * queryable), an `email` credential is translated into a lookup on its
 * deterministic keyed hash.
 */
class HashedEloquentUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (isset($credentials['email']) && is_string($credentials['email'])) {
            $credentials['email_hash'] = EmailHasher::hash($credentials['email']);
            unset($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
