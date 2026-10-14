<?php

use App\Http\Controllers\Settings\DataPrivacyController;
use App\Http\Controllers\Settings\IdentityController;
use App\Http\Controllers\Settings\InvitationController;
use App\Http\Controllers\Settings\LinkedAccountController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:settings')
        ->name('profile.update');

    // Account deletion stays reachable before verification: a member whose
    // registration email is wrong must still be able to close their
    // account. It demands the current password: throttle the endpoint so
    // a stolen session cannot brute-force it online.
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:password')
        ->name('profile.destroy');

    Route::get('settings/identity', [IdentityController::class, 'edit'])->name('identity.edit');

    Route::get('settings/data', [DataPrivacyController::class, 'edit'])->name('data.edit');
    Route::get('settings/data/export', [DataPrivacyController::class, 'export'])
        ->middleware('throttle:data-export')
        ->name('data.export');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('settings/identity', [IdentityController::class, 'update'])
        ->middleware('throttle:settings')
        ->name('identity.update');

    // Signed confirmation link for a school email change: proves the
    // member controls the new address before it anchors their identity.
    Route::get('settings/identity/school-email/verify/{id}/{hash}', [IdentityController::class, 'verifySchoolEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('school-email.verify');

    Route::get('settings/invitations', [InvitationController::class, 'edit'])->name('invitations.edit');
    Route::post('settings/invitations', [InvitationController::class, 'store'])
        ->middleware('throttle:invitations')
        ->name('invitations.store');
    Route::delete('settings/invitations/{invitation}', [InvitationController::class, 'destroy'])
        ->middleware('throttle:invitations')
        ->name('invitations.destroy');

    Route::get('settings/accounts', [LinkedAccountController::class, 'edit'])->name('accounts.edit');
    Route::put('settings/accounts', [LinkedAccountController::class, 'update'])
        ->middleware('throttle:settings')
        ->name('accounts.update');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:password')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
    Route::inertia('settings/accessibility', 'settings/Accessibility')->name('accessibility.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
