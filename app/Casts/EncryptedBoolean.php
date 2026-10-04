<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts a boolean attribute at rest. Laravel has no native
 * "encrypted:boolean" cast, so booleans are serialized as "1" or "0"
 * inside the encrypted envelope.
 *
 * @implements CastsAttributes<bool|null, bool|null>
 */
class EncryptedBoolean implements CastsAttributes
{
    /**
     * Transform the raw encrypted value into a boolean.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, mixed $value, array $attributes): ?bool
    {
        return $value !== null ? Crypt::decryptString($value) === '1' : null;
    }

    /**
     * Encrypt the boolean value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        return $value !== null ? Crypt::encryptString($value ? '1' : '0') : null;
    }

    /**
     * Compare the original and the new raw values by their decrypted
     * content, so an unchanged value is not marked dirty despite the
     * randomized ciphertexts.
     *
     * @param  Model  $model
     */
    public function compare($model, string $key, mixed $original, mixed $value): bool
    {
        if ($original === null || $value === null) {
            return $original === $value;
        }

        return Crypt::decryptString($original) === Crypt::decryptString($value);
    }
}
