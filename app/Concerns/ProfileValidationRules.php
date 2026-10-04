<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\EmailHasher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?string $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?string $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $this->uniqueEmailRule($userId),
        ];
    }

    /**
     * Rule enforcing the uniqueness of the email address. The email column
     * is encrypted, so the check runs on its deterministic hash column.
     */
    private function uniqueEmailRule(?string $userId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($userId): void {
            if ($value === null) {
                return;
            }

            $takenByAnotherUser = User::query()
                ->where('email_hash', EmailHasher::hash($value))
                ->when($userId !== null, fn ($query) => $query->whereKeyNot($userId))
                ->exists();

            if ($takenByAnotherUser) {
                $fail(__('validation.unique'));
            }
        };
    }
}
