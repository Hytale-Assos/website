<?php

namespace App\Http\Requests\Settings;

use App\Models\Invitation;
use App\Models\User;
use App\Support\EmailHasher;
use App\Support\SchoolEmails;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvitationStoreRequest extends FormRequest
{
    /**
     * Only internal members can invite.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_internal;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // School emails register on their own: no invitation needed.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && SchoolEmails::isSchoolEmail($value)) {
                        $fail(__('This email can register on its own: invitations are for external guests.'));
                    }
                },
                // An email with an account has nothing to be invited to.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $registered = User::query()
                        ->where('email_hash', EmailHasher::hash($value))
                        ->exists();

                    if ($registered) {
                        $fail(__('This email already has an account.'));
                    }
                },
                // One usable invitation per email at a time; the partial
                // unique index is the last line of defense.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $invited = Invitation::query()
                        ->where('email_hash', EmailHasher::hash($value))
                        ->usable()
                        ->exists();

                    if ($invited) {
                        $fail(__('This email has already been invited.'));
                    }
                },
            ],
        ];
    }

    /**
     * Quota: accepted invitations count permanently, pending ones until
     * they are revoked or expire. The budget is accepted + pending.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $active = Invitation::query()
                    ->where('inviter_user_id', $this->user()->id)
                    ->where(function ($query): void {
                        $query->whereNotNull('accepted_at')->orWhere(function ($query): void {
                            $query->usable();
                        });
                    })
                    ->count();

                if ($active >= (int) config('members.invitation_limit')) {
                    $validator->errors()->add('email', __('You have reached your invitation limit.'));
                }
            },
        ];
    }
}
