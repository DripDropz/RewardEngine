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
        Route::get('providers', [AuthController::class, 'providers']);
        Route::get('init/{publicApiKey}/{authProvider}', [AuthController::class, 'init'])->middleware(['web'])->name('api.v1.auth.init');

        // Private Endpoints
        Route::post('check', [AuthController::class, 'check'])->middleware(ProjectAPIKeyAuth::class);

    });

});
