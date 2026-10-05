<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\InvitationStoreRequest;
use App\Models\Invitation;
use App\Support\Toast;
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
        $this->authorize('manage', Invitation::class);

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
     * first (see Invitation::supersedeExpiredFor) so the new invitation
     * can take the unique email slot.
     */
    public function store(InvitationStoreRequest $request): RedirectResponse
    {
        Invitation::supersedeExpiredFor($request->validated('email'));

        Invitation::create([
            'inviter_user_id' => $request->user()->id,
            'email' => $request->validated('email'),
            'expires_at' => now()->addDays((int) config('members.invitation_validity_days')),
        ]);

        Toast::success(__('Invitation sent.'));

        return to_route('invitations.edit');
    }

    /**
     * Revoke one of the member's pending invitations.
     */
    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        $invitation->update(['revoked_at' => now()]);

        Toast::success(__('Invitation revoked.'));

        return to_route('invitations.edit');
    }
}
