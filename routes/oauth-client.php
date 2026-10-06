<?php

use App\Http\Controllers\Settings\LinkedAccountOAuthController;
use Illuminate\Support\Facades\Route;

// Third-party OAuth flows linking an existing account to a provider
// (Discord today). The website is the OAuth client here. The route
// names are the contract: the frontend uses the generated linked.*
// functions and the provider application registers the callback URL.
Route::middleware(['auth'])->group(function () {
    Route::get('auth/{provider}/redirect', [LinkedAccountOAuthController::class, 'redirect'])
        ->middleware('throttle:oauth')
        ->name('linked.redirect');
    Route::get('auth/{provider}/callback', [LinkedAccountOAuthController::class, 'callback'])
        ->middleware('throttle:oauth')
        ->name('linked.callback');
    Route::delete('settings/accounts/{provider}', [LinkedAccountOAuthController::class, 'destroy'])->name('linked.unlink');
});
