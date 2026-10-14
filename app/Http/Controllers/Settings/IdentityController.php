<?php

namespace App\Http\Controllers\Settings;

use App\Enums\GradeLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\IdentityUpdateRequest;
use App\Notifications\SchoolEmailConfirmation;
use App\Support\EmailHasher;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class IdentityController extends Controller
{
    /**
     * Show the user's identity settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Identity', [
            'gradeLevels' => GradeLevel::options(),
        ]);
    }

    /**
     * Update the user's identification data.
     *
     * The member status is frozen at registration and never changes
     * here. A school email change is never applied directly: the new
     * address is stored as a pending claim and a confirmation link is
     * sent to it; only clicking that link (verifySchoolEmail) swaps
     * the confirmed school email.
     */
    public function update(IdentityUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validated();
        $newSchoolEmail = Arr::pull($validated, 'new_school_email');

        $user->update($validated);

        if ($newSchoolEmail !== null && $newSchoolEmail !== $user->school_email) {
            $user->forceFill([
                'pending_school_email' => $newSchoolEmail,
            ])->save();

            $expiresAt = Carbon::now()->addHours(24);

            Notification::route('mail', $newSchoolEmail)->notify(
                new SchoolEmailConfirmation(
                    URL::temporarySignedRoute(
                        'school-email.verify',
                        $expiresAt,
                        ['id' => $user->id, 'hash' => EmailHasher::hash($newSchoolEmail)],
                    ),
                    $expiresAt,
                ),
            );

            Toast::success(__('Identity updated. A confirmation link has been sent to your new school email address.'));

            return to_route('identity.edit');
        }

        Toast::success(__('Identity updated.'));

        return to_route('identity.edit');
    }

    /**
     * Confirm a pending school email change: the signed link proves the
     * member controls the new address, so the pending claim becomes the
     * confirmed school email.
     */
    public function verifySchoolEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $pending = $user->pending_school_email;

        if ($pending === null
            || $user->id !== $request->route('id')
            || ! hash_equals(EmailHasher::hash($pending), (string) $request->route('hash'))) {
            Toast::error(__('This school email confirmation link is invalid or has expired.'));

            return to_route('identity.edit');
        }

        $user->forceFill([
            'school_email' => $pending,
            'pending_school_email' => null,
        ])->save();

        Toast::success(__('School email updated.'));

        return to_route('identity.edit');
    }
}
