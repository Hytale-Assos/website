<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Support\IdHasher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LinkedAccountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->user()->hytale_account_verified_at !== null) {
            return [
                'hytale_nickname' => ['prohibited'],
                'hytale_id' => ['prohibited'],
            ];
        }

        return [
            'hytale_nickname' => ['nullable', 'string', 'max:255'],
            'hytale_id' => ['nullable', 'uuid', $this->uniqueHytaleIdRule()],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hytale_nickname.prohibited' => __('Your Hytale account is verified and can no longer be changed here.'),
            'hytale_id.prohibited' => __('Your Hytale account is verified and can no longer be changed here.'),
        ];
    }

    /**
     * Rule enforcing the uniqueness of the Hytale id. The id column is
     * encrypted, so the check runs on its deterministic hash column.
     */
    private function uniqueHytaleIdRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }

            $takenByAnotherUser = User::query()
                ->where('hytale_id_hash', IdHasher::hash($value))
                ->whereKeyNot($this->user()->id)
                ->exists();

            if ($takenByAnotherUser) {
                $fail(__('validation.unique'));
            }
        };
    }
}
