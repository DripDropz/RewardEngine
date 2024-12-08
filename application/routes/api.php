<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventsController;
use App\Http\Controllers\API\StatsController;
use App\Http\Middleware\ProjectApiKeyAuth;
use Illuminate\Support\Facades\Route;

/**
 * V1 API Implementation
 */
Route::prefix('v1')->name('api.v1.')->group(static function ()
{
    // ->middleware(['throttle:api-auth'])

    // Auth
    Route::prefix('auth')->name('auth.')->group(static function ()
    {

        // Public Endpoints
        Route::get('providers', [AuthController::class, 'providers'])->name('providers');
        Route::get('init/{publicApiKey}/{authProvider}', [AuthController::class, 'init'])->middleware(['web'])->name('init');
        Route::post('initWallet/{publicApiKey}', [AuthController::class, 'initWallet'])->name('initWallet');
        Route::post('verifyWallet/{publicApiKey}', [AuthController::class, 'verifyWallet'])->name('verifyWallet');
        Route::get('check/{publicApiKey}', [AuthController::class, 'check'])->name('check');
        Route::post('refresh/{publicApiKey}', [AuthController::class, 'refresh'])->name('refresh');

    });

    // Stats
    Route::prefix('stats')->name('stats.')->group(static function ()
    {
        // Public Endpoints
        Route::get('global/{publicApiKey}', [StatsController::class, 'global'])->name('global');
        Route::get('session/{publicApiKey}/{reference}', [StatsController::class, 'session'])->name('session');
        Route::post('session/{publicApiKey}/link-wallet-address', [StatsController::class, 'sessionLinkWalletAddress'])->name('session.link-wallet-address');
        Route::get('session/{publicApiKey}/{sessionId}/link-discord-account', [StatsController::class, 'sessionLinkDiscordAccount'])->middleware(['web'])->name('session.link-discord-account');
        Route::get('leaderboard/{publicApiKey}', [StatsController::class, 'leaderboard'])->name('leaderboard');
        Route::get('leaderboard/{publicApiKey}/qualifiers', [StatsController::class, 'leaderboardQualifiers'])->name('leaderboard.qualifiers');

    });

    // Private Endpoints
    Route::middleware(ProjectApiKeyAuth::class)->group(static function ()
    {

        // Events
         Route::post('events', [EventsController::class, 'store']);

    });

});
