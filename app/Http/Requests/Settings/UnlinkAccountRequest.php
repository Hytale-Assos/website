<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Support\LinkableProviders;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UnlinkAccountRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * The provider whitelist runs before the password check so an
     * unsupported provider answers 404 without prompting for
     * credentials: no information beyond the whitelist leaks.
     */
    public function authorize(): bool
    {
        abort_unless(LinkableProviders::exists((string) $this->route('provider')), 404);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Unlinking strips a proven identity from the account: a
            // stolen session must not be able to do it in one click.
            'password' => $this->currentPasswordRules(),
        ];
    }
}
