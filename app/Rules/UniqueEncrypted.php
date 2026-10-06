<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Uniqueness of an encrypted column, checked through its deterministic
 * blind-index hash: the value is hashed with the given hasher, looked up
 * in the companion hash column, and rejected when another user already
 * holds it. The single definition of the project's core privacy pattern
 * for uniqueness (emails, school emails, linked account ids).
 */
class UniqueEncrypted implements ValidationRule
{
    /**
     * @param  string  $hashColumn  the blind-index column carrying the uniqueness
     * @param  Closure(string): string  $hasher  computes the blind index of a value
     * @param  string|null  $ignoreUserId  the user exempt from the check
     */
    public function __construct(
        protected string $hashColumn,
        protected Closure $hasher,
        protected ?string $ignoreUserId = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $takenByAnotherUser = User::query()
            ->where($this->hashColumn, ($this->hasher)((string) $value))
            ->when($this->ignoreUserId !== null, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($takenByAnotherUser) {
            $fail(__('validation.unique'));
        }
    }
}
