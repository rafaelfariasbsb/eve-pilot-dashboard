<?php

use App\Http\Controllers\Auth\EveAuthController;
use App\Http\Controllers\BlueprintController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::view('/', 'auth.login')->name('home');

// EVE SSO Auth
Route::get('/auth/eve/redirect', [EveAuthController::class, 'redirect'])->name('eve.login');
Route::get('/auth/eve/callback', [EveAuthController::class, 'callback'])->name('eve.callback');
Route::post('/auth/logout', [EveAuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/blueprints', [BlueprintController::class, 'index'])->name('blueprints.index');
    Route::get('/blueprints/{typeId}', [BlueprintController::class, 'show'])->name('blueprints.show');

    // Manual sync trigger
    Route::post('/sync', function () {
        $character = auth()->user()->mainCharacter();
        if ($character) {
            \App\Jobs\SyncCharacterData::dispatch($character->character_id);
            return back()->with('status', 'Sync job dispatched! Data will update shortly.');
        }
        return back()->with('error', 'No character found.');
    })->name('sync');
});
