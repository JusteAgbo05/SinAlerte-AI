<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Operator\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

// Le middleware Inertia est appliqué ici, ce qui évite de modifier bootstrap/app.php.
Route::middleware(HandleInertiaRequests::class)->group(function () {

    // --- Parcours citoyen (sans compte) ---------------------------------
    Route::inertia('/', 'Home')->name('home');

    Route::get('/signaler', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/signaler', [ReportController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('reports.store');

    Route::get('/suivi', [TrackingController::class, 'lookup'])->name('tracking.lookup');
    Route::post('/suivi', [TrackingController::class, 'find'])
        ->middleware('throttle:30,1')
        ->name('tracking.find');
    Route::get('/suivi/{code}', [TrackingController::class, 'show'])->name('tracking.show');

    // --- Authentification des opérateurs (pas d'inscription publique) ---
    Route::get('/connexion', [AuthController::class, 'create'])
        ->middleware('guest')
        ->name('login');
    Route::post('/connexion', [AuthController::class, 'store'])
        ->middleware(['guest', 'throttle:6,1']);
    Route::post('/deconnexion', [AuthController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    // --- Espace opérateur -------------------------------------------------
    Route::middleware('auth')->prefix('operateur')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::patch('/signalements/{report}', [DashboardController::class, 'update'])
            ->name('operator.reports.update');
        Route::get('/signalements/{report}/photo', [DashboardController::class, 'photo'])
            ->name('operator.reports.photo');
        Route::get('/signalements/{report}/audio', [DashboardController::class, 'audio'])
            ->name('operator.reports.audio');
    });
});
