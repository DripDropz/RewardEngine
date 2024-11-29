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


// TEST ROUTE :: START
Route::get('test', static function() {
    if (app()->environment('local')) {

        $eventData = \App\Models\EventData::query()
            ->whereIn('event_id', [
                'shardId-000000000003:49657946261021521641665930132603933531225061505022033970',
                'shardId-000000000001:49657946256338365149974499463889294341441092324903878674',
                'shardId-000000000001:49657946256338365149974499464011395849222169871549202450',
                'shardId-000000000001:49657946256338365149974489397590745224747565633295613970',
                'shardId-000000000000:49657946259170559790187531133901068268176151249074257922',
                'shardId-000000000003:49657946261021521641666007217825748872492731357389127730',
            ])
            ->get();

        foreach ($eventData as $eventDatum) {
            (new \App\Jobs\HydraDoomEventParserJob($eventDatum))->handle();
        }

        dd('done');
    }
});
// TEST ROUTE :: END
