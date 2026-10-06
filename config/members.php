<?php

return [

    /*
    |--------------------------------------------------------------------------
    | School email domains
    |--------------------------------------------------------------------------
    |
    | Email domains that identify school members, lowercase and without
    | wildcards. A registration whose email belongs to one of these
    | domains creates an internal account without an invitation; every
    | other registration requires a valid invitation.
    |
    | SCHOOL_EMAIL_DOMAINS is a comma-separated list. An empty list means
    | no domain is trusted: every registration then requires an
    | invitation.
    |
    */

    'school_email_domains' => array_values(array_filter(array_map(
        fn (string $domain) => strtolower(trim($domain)),
        explode(',', (string) env('SCHOOL_EMAIL_DOMAINS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Invitation limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of active invitations a single internal member can
    | hold. Accepted invitations count permanently, pending ones until
    | they are revoked or expire. Revoked and expired invitations free
    | the slot: the budget is accepted + pending.
    |
    */

    'invitation_limit' => (int) env('MEMBERS_INVITATION_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Invitation validity
    |--------------------------------------------------------------------------
    |
    | Number of days before a pending invitation expires without being
    | consumed. Expired invitations can no longer be used to register.
    |
    */

    'invitation_validity_days' => 7,

];
