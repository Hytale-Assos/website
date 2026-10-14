<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Internal members manage invitations. Defense in depth: the route is
     * already gated behind a verified email, but the policy also refuses
     * unverified members so a future route change cannot silently reopen
     * the invitation flow to an unproven school identity.
     */
    public function manage(User $user): bool
    {
        return (bool) $user->is_internal && $user->hasVerifiedEmail();
    }

    /**
     * Inviters can revoke their own invitations as long as they are
     * still pending: not consumed and not already revoked.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $invitation->inviter_user_id === $user->id
            && $invitation->accepted_at === null
            && $invitation->revoked_at === null;
    }
}
