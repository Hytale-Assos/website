<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Enums\GradeLevel;
use App\Rules\UniqueEncrypted;
use App\Support\EmailHasher;
use App\Support\SchoolEmails;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IdentityUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * The member status is frozen at registration: it is never part of
     * the update. Internal members maintain their identification data;
     * their school email is anchored to their verified registration
     * email and can only be replaced through the pending-claim flow:
     * a new address is accepted as `new_school_email`, stored as a
     * pending claim, and only becomes the school email once the member
     * confirms it by clicking a signed link sent to that address.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ((bool) $this->user()?->is_internal) {
            return [
                'firstname' => ['required', 'string', 'max:255'],
                'lastname' => ['required', 'string', 'max:255'],
                'grade_level' => ['required', Rule::enum(GradeLevel::class)],
                'new_school_email' => [
                    'nullable',
                    'string',
                    'email',
                    'max:255',
                    $this->schoolDomainRule(),
                    $this->uniqueSchoolEmailRule(),
                    $this->uniquePendingSchoolEmailRule(),
                ],
            ];
        }

        return [
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Normalize the requested school email to its canonical form: the
     * school email identity is case-insensitive, so a differently cased
     * resubmission of the same address must not read as a change.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('new_school_email')) {
            $this->merge([
                'new_school_email' => Str::lower(trim((string) $this->input('new_school_email'))),
            ]);
        }
    }

    /**
     * Requesting a school email change is a security-sensitive
     * operation (it re-anchors the internal identity): it is only
     * accepted when re-authenticated with the current password.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('current_password', $this->currentPasswordRules(), function (): bool {
            return $this->filled('new_school_email');
        });
    }

    /**
     * Rule enforcing that the school email belongs to a configured school
     * domain: it anchors the internal status, anything else has no place
     * there.
     */
    private function schoolDomainRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && ! SchoolEmails::isSchoolEmail($value)) {
                $fail(__('The :attribute must be a school email address.', ['attribute' => $attribute]));
            }
        };
    }

    /**
     * Rule enforcing the uniqueness of the school email: one account per
     * school address, against already-confirmed claims.
     */
    private function uniqueSchoolEmailRule(): UniqueEncrypted
    {
        return new UniqueEncrypted(
            'school_email_hash',
            EmailHasher::hash(...),
            $this->user()->id,
        );
    }

    /**
     * Rule enforcing the uniqueness of the school email against other
     * members' pending claims: two members must not race for the same
     * address, even before confirmation.
     */
    private function uniquePendingSchoolEmailRule(): UniqueEncrypted
    {
        return new UniqueEncrypted(
            'pending_school_email_hash',
            EmailHasher::hash(...),
            $this->user()->id,
        );
    }
}
