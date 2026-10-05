<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Whether an email address belongs to one of the configured school
 * domains (config/members.php). Registration with a matching domain
 * creates an internal account without an invitation.
 */
class SchoolEmails
{
    /**
     * Check whether the given email address belongs to a school domain.
     *
     * The email is normalized the same way EmailHasher does, so a value
     * and its blind index always agree on which side of the gate it
     * belongs to.
     */
    public static function isSchoolEmail(string $email): bool
    {
        $domain = Str::afterLast(Str::lower(trim($email)), '@');

        return in_array($domain, config('members.school_email_domains'), true);
    }
}
