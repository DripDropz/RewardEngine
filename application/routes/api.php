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

    // Auth
    Route::prefix('auth')->name('auth.')->middleware(['throttle:api-auth'])->group(static function ()
    {

        // Public Endpoints
        Route::get('providers', [AuthController::class, 'providers'])->name('providers');
        Route::get('init/{publicApiKey}/{authProvider}', [AuthController::class, 'init'])->middleware(['web'])->name('init');
        Route::post('initWallet/{publicApiKey}', [AuthController::class, 'initWallet'])->name('initWallet');
        Route::post('verifyWallet/{publicApiKey}', [AuthController::class, 'verifyWallet'])->name('verifyWallet');
        Route::get('check/{publicApiKey}', [AuthController::class, 'check'])->name('check');

    });

    // Stats
    Route::prefix('stats')->name('stats.')->middleware(['throttle:api-auth'])->group(static function ()
    {
        // Public Endpoints
        Route::get('global/{publicApiKey}', [StatsController::class, 'global']);
        Route::get('session/{publicApiKey}/{reference}', [StatsController::class, 'session']);

    });

    // Private Endpoints
    Route::middleware(ProjectApiKeyAuth::class)->group(static function ()
    {

        // Events
         Route::post('events', [EventsController::class, 'store']);

    });

});
