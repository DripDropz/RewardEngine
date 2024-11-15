<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index']);

Route::middleware(['auth', 'verified'])->group(static function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::prefix('projects')->group(static function () {
        Route::get('/', [ProjectsController::class, 'index'])->name('projects.index');
        Route::get('{projectId}', [ProjectsController::class, 'show'])->name('projects.show');
        Route::get('create', [ProjectsController::class, 'create'])->name('projects.create');
        Route::post('store', [ProjectsController::class, 'store'])->name('projects.store');
    });

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// TEST ROUTE :: START
if (app()->environment('local')) {

    Route::get('test/{authProvider}', static function ($authProvider) {
        return \Laravel\Socialite\Facades\Socialite::driver($authProvider)->redirect();
    });

    Route::get('social-auth-callback/{authProvider}', static function ($authProvider) {
        $socialUser = \Laravel\Socialite\Facades\Socialite::driver($authProvider)->user();
        dd($socialUser);
    });

}
// TEST ROUTE :: END
