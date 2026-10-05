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
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/identity', [IdentityController::class, 'edit'])->name('identity.edit');
    Route::put('settings/identity', [IdentityController::class, 'update'])->name('identity.update');

    Route::get('settings/data', [DataPrivacyController::class, 'edit'])->name('data.edit');
    Route::get('settings/data/export', [DataPrivacyController::class, 'export'])->name('data.export');

    Route::get('settings/invitations', [InvitationController::class, 'edit'])->name('invitations.edit');
    Route::post('settings/invitations', [InvitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('invitations.store');
    Route::delete('settings/invitations/{invitation}', [InvitationController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('invitations.destroy');

    Route::get('settings/accounts', [LinkedAccountController::class, 'edit'])->name('accounts.edit');
    Route::put('settings/accounts', [LinkedAccountController::class, 'update'])->name('accounts.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
