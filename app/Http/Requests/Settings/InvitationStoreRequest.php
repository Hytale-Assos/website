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
     * Internal members can invite (InvitationPolicy::manage).
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage', Invitation::class);
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
                // School emails register on their own: no invitation needed.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && SchoolEmails::isSchoolEmail($value)) {
                        $fail(__('This email can register on its own: invitations are for external guests.'));
                    }
                },
                // One usable invitation per email at a time; the partial
                // unique index is the last line of defense.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    // Whether the email already has an account or already
                    // holds an invitation must be indistinguishable: a
                    // single neutral refusal for both. Only the member's
                    // OWN pending invitation stays identifiable — it is
                    // their history, not an oracle on other people.
                    $ownPending = Invitation::query()
                        ->where('email_hash', EmailHasher::hash($value))
                        ->where('inviter_user_id', $this->user()->id)
                        ->usable()
                        ->exists();

                    if ($ownPending) {
                        $fail(__('You have already invited this email and the invitation is still pending.'));

                        return;
                    }

                    $registered = User::query()
                        ->where('email_hash', EmailHasher::hash($value))
                        ->exists();

                    $invited = Invitation::query()
                        ->where('email_hash', EmailHasher::hash($value))
                        ->usable()
                        ->exists();

                    if ($registered || $invited) {
                        $fail(__('This email cannot receive an invitation.'));
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
