<?php

namespace App\Http\Requests\Settings;

use App\Enums\GradeLevel;
use App\Models\User;
use App\Support\EmailHasher;
use App\Support\SchoolEmails;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdentityUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The member status is frozen at registration: it is never part of
     * the update. Internal members maintain their full identification
     * data plus the school email anchoring their status; external
     * members may only fill in their name, optionally.
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
                'school_email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    $this->schoolDomainRule(),
                    $this->uniqueSchoolEmailRule(),
                ],
            ];
        }

        return [
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
        ];
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
     * student. The column is encrypted, so the check runs on its
     * deterministic hash column, ignoring the current user.
     */
    private function uniqueSchoolEmailRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            $takenByAnotherUser = User::query()
                ->where('school_email_hash', EmailHasher::hash($value))
                ->whereKeyNot($this->user()->id)
                ->exists();

            if ($takenByAnotherUser) {
                $fail(__('validation.unique'));
            }
        };
    }
}
