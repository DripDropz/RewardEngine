<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SocialAuthCallbackController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index']);
Route::get('demo', fn() => view('demo'));
Route::get('social-auth-callback/{authProvider}', [SocialAuthCallbackController::class, 'handle']);

Route::middleware(['auth', 'verified'])->group(static function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::prefix('projects')->group(static function () {
        Route::get('/', [ProjectsController::class, 'index'])->name('projects.index');
        Route::get('create', [ProjectsController::class, 'create'])->name('projects.create');
        Route::post('store', [ProjectsController::class, 'store'])->name('projects.store');
        Route::get('{projectId}', [ProjectsController::class, 'show'])->name('projects.show');
        Route::post('{projectId}', [ProjectsController::class, 'update'])->name('projects.update');
    });

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
