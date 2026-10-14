<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse as FailedPasswordResetResponseContract;

/**
 * Drop-in replacement for Fortify's failed password-reset response that
 * reports a wrong (email, token) pair as an invalid token, never as a
 * missing user.
 *
 * Without it, the broker's INVALID_USER status would reveal which emails
 * belong to member accounts when a reset is attempted with a token that
 * was issued for another address.
 */
class NeutralFailedPasswordResetResponse implements FailedPasswordResetResponseContract
{
    /**
     * Create a new response instance.
     */
    public function __construct(public readonly string $status) {}

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        // Any email/token mismatch reads as an invalid token; other
        // statuses (e.g. weak password) keep their own message.
        $status = $this->status === Password::INVALID_USER
            ? Password::INVALID_TOKEN
            : $this->status;

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
