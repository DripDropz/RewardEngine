<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Middleware\ProjectAPIKeyAuth;
use Illuminate\Support\Facades\Route;

/**
 * V1 API Implementation
 */
Route::prefix('v1')->group(static function ()
{

    // Auth
    Route::prefix('auth')->group(static function ()
    {
        // Public Endpoints
        Route::get('providers', [AuthController::class, 'providers'])->name('api.v1.auth.providers');
        Route::get('init/{publicApiKey}/{authProvider}', [AuthController::class, 'init'])->middleware(['web'])->name('api.v1.auth.init');
        Route::get('check/{publicApiKey}', [AuthController::class, 'check'])->name('api.v1.auth.check');

        // Private Endpoints
        // Route::post('xxx', [AuthController::class, 'xxx'])->middleware(ProjectAPIKeyAuth::class);

    });

});
