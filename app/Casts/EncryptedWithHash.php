<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts the attribute at rest and keeps a deterministic SHA-256 hash of
 * the value in sync in a companion column. The hash column carries the
 * uniqueness constraint, since encrypted values cannot be queried.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedWithHash implements CastsAttributes
{
    public function __construct(
        protected string $hashColumn,
    ) {}

    /**
     * Transform the raw encrypted value into the decrypted one.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, mixed $value, array $attributes): ?string
    {
        return $value !== null ? Crypt::decryptString($value) : null;
    }

    /**
     * Encrypt the value and sync its hash column.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, string|null>
     */
    public function set($model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null, $this->hashColumn => null];
        }

        return [
            $key => Crypt::encryptString((string) $value),
            $this->hashColumn => $this->hashValue((string) $value),
        ];
    }

    /**
     * Compute the deterministic hash of the value.
     */
    protected function hashValue(string $value): string
    {
        return hash('sha256', $value);
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
