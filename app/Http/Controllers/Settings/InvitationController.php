<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\InvitationStoreRequest;
use App\Models\Invitation;
use App\Support\EmailHasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Show the member's sent invitations.
     */
    public function edit(Request $request): Response
    {
        abort_unless((bool) $request->user()->is_internal, 403);

        return Inertia::render('settings/Invitations', [
            'invitations' => $request->user()
                ->invitations()
                ->latest('created_at')
                ->get()
                ->map(fn (Invitation $invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'status' => $invitation->status(),
                    'created_at' => $invitation->created_at?->toIso8601String(),
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ])
                ->values()
                ->all(),
            'limit' => (int) config('members.invitation_limit'),
        ]);
    }

    /**
     * Send a new invitation.
     *
     * Expired invitations for the same email are stamped as superseded
     * first: the partial unique index only knows about accepted_at and
     * revoked_at (now() is not immutable, so expiry cannot be part of the
     * index predicate), so stamping frees the unique slot for the new
     * invitation.
     */
    public function store(InvitationStoreRequest $request): RedirectResponse
    {
        Invitation::query()
            ->where('email_hash', EmailHasher::hash($request->validated('email')))
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', now())
            ->update(['revoked_at' => now()]);

        Invitation::create([
            'inviter_user_id' => $request->user()->id,
            'email' => $request->validated('email'),
            'expires_at' => now()->addDays((int) config('members.invitation_validity_days')),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('invitations.edit');
    }

    /**
     * Revoke one of the member's pending invitations.
     */
    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->inviter_user_id === $request->user()->id, 403);
        abort_unless($invitation->accepted_at === null && $invitation->revoked_at === null, 403);

        $invitation->update(['revoked_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return to_route('invitations.edit');
    }
}
