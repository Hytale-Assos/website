<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;

/**
 * Drop-in replacement for Fortify's failed reset-link response that is
 * indistinguishable from the successful one.
 *
 * Whether or not the submitted email belongs to a member, the response is
 * the same (same status code, same message, no validation error): the
 * public reset-link request flow must not let anyone enumerate the
 * members' email addresses.
 */
class NeutralFailedPasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponseContract
{
    /**
     * Create a new response instance.
     */
    public function __construct(public readonly string $status)
    {
        // The broker status is intentionally ignored: the response must
        // not depend on whether the email matched an account.
    }

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => __(Password::RESET_LINK_SENT)], 200)
            : back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
