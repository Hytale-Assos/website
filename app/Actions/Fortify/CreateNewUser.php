<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Invitation;
use App\Models\User;
use App\Rules\UniqueEncrypted;
use App\Support\EmailHasher;
use App\Support\SchoolEmails;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * The member status is derived from the registration email and frozen
     * from that point on: a school-domain email creates an internal
     * account anchored on the school email, anything else an external
     * account that requires a valid invitation.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $isInternal = SchoolEmails::isSchoolEmail($input['email'] ?? '');

        $rules = [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ];

        // The registration email of an internal member becomes their
        // school email: it must not already be claimed as another
        // member's school email, or the INSERT would violate the blind
        // index and surface as a raw 500. Refused cleanly instead.
        if ($isInternal) {
            $rules['email'][] = new UniqueEncrypted(
                'school_email_hash',
                EmailHasher::hash(...),
            );
        }

        Validator::make($input, $rules)->validate();

        $invitation = null;

        if (! $isInternal) {
            $invitation = Invitation::usableFor($input['email']);

            if ($invitation === null) {
                throw ValidationException::withMessages([
                    'email' => __('Registration is open to school members. External accounts require an invitation.'),
                ]);
            }
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'school_email' => $isInternal ? $input['email'] : null,
        ]);

        // The member status flags are excluded from the model's fillable
        // list: they are privilege markers derived from the registration
        // email and must never be mass-assignable.
        $user->forceFill([
            'is_internal' => $isInternal,
            'is_external' => ! $isInternal,
        ])->save();

        $invitation?->consume($user);

        return $user;
    }
}
