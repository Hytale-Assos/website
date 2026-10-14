<?php

namespace App\Casts;

use App\Support\IdHasher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Encrypts the attribute at rest and keeps a deterministic SHA-256 hash of
 * the value in sync in a companion column. The hash column carries the
 * uniqueness constraint, since encrypted values cannot be queried.
 *
 * The value is normalized to its canonical form (lowercase, trimmed)
 * before encryption: identifiers are case-insensitive, so the stored
 * value, its blind index, and everything derived from them (cache keys,
 * payloads sent to the core) stay consistent whatever case the member
 * originally typed. Every write path — manual form, OAuth callback,
 * factory — goes through this cast and is therefore canonical.
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

        $value = Str::lower(trim((string) $value));

        return [
            $key => Crypt::encryptString($value),
            $this->hashColumn => $this->hashValue($value),
        ];
    }

    /**
     * Compute the deterministic hash of the value.
     */
    protected function hashValue(string $value): string
    {
        return IdHasher::hash($value);
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
