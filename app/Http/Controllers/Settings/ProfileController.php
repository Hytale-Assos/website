<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Notifications\EmailAddressChanged;
use App\Support\Toast;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * A changed email address is a security-sensitive operation
     * (re-authenticated by ProfileUpdateRequest with the current
     * password): the new address must be proven again before the
     * member regains access to the verified-only routes, the previous
     * address gets a warning mail, and every other session is evicted
     * like after a password change.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousEmail = $user->email;

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->wasChanged('email')) {
            $user->sendEmailVerificationNotification();

            Notification::route('mail', $previousEmail)->notify(new EmailAddressChanged);

            $user->forceFill([
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        Toast::success(__('Profile updated.'));

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     *
     * GDPR erasure: the account is removed for real — no tombstone, no
     * retained personal data. Pending invitations sent by the member
     * are revoked (a deleted inviter must not keep granting access),
     * consumed invitations keep their row with the member's column
     * set to null, every session of the account is purged, and the
     * passkeys cascade away with the row.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->invitations()
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        Auth::logout();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $user->forceDelete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
