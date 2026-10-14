<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }

    /**
     * Normalize the email address to its canonical form (lowercase,
     * trimmed): the login identity of an email is case-insensitive, so
     * re-submitting the same address with different casing is not an
     * email change and must not re-arm the checks below.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * Changing the email address a member signs in with is as
     * sensitive as a password change: it is only accepted when the
     * request is re-authenticated with the current password. A mere
     * rename keeps working without it.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('current_password', $this->currentPasswordRules(), function (): bool {
            return $this->isChangingEmail();
        });
    }

    private function isChangingEmail(): bool
    {
        return (string) $this->input('email') !== Str::lower((string) $this->user()->email);
    }
}
