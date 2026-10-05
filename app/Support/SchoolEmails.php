<?php

namespace App\Support;

/**
 * Whether an email address belongs to one of the configured school
 * domains (config/members.php). Registration with a matching domain
 * creates an internal account without an invitation.
 */
class SchoolEmails
{
    /**
     * Check whether the given email address belongs to a school domain.
     */
    public static function isSchoolEmail(string $email): bool
    {
        $domain = strtolower(substr($email, (int) strrpos($email, '@') + 1));

        return in_array($domain, config('members.school_email_domains'), true);
    }
}
