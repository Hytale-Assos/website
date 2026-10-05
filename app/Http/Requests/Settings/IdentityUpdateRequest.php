<?php

namespace App\Http\Requests\Settings;

use App\Enums\GradeLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdentityUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The `status` radio field is the exclusive internal/external choice;
     * the controller maps it onto the is_internal/is_external flags.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', Rule::enum(GradeLevel::class)],
            'status' => ['required', 'string', Rule::in(['internal', 'external'])],
        ];
    }
}
