<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Support\SchoolEmails;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * The member status is derived from the registration email and frozen
     * from that point on: a school-domain email creates an internal
     * account anchored on the school email, everything else an external
     * account.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $isInternal = SchoolEmails::isSchoolEmail($input['email']);

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'school_email' => $isInternal ? $input['email'] : null,
            'is_internal' => $isInternal,
            'is_external' => ! $isInternal,
        ]);
    }
}
