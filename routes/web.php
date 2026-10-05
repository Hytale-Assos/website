<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ModController;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\WhitelistController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('servers', [ServerController::class, 'index'])->name('servers.index');

    Route::get('whitelist', [WhitelistController::class, 'index'])->name('whitelist.index');
    Route::post('whitelist', [WhitelistController::class, 'store'])
        ->middleware('throttle:whitelist')
        ->name('whitelist.store');
    Route::delete('whitelist/{entry}', [WhitelistController::class, 'destroy'])
        ->middleware('throttle:whitelist')
        ->name('whitelist.destroy');

    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');

    Route::get('maps', [MapController::class, 'index'])->name('maps.index');

    Route::get('mods', [ModController::class, 'index'])->name('mods.index');

    Route::get('points', [PointsController::class, 'index'])->name('points.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/oauth-client.php';
