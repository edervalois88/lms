<?php

use App\Http\Controllers\Student\OnboardingController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Assessment\QuizController;
use App\Http\Controllers\Assessment\SimulatorController;
use App\Http\Controllers\Progress\ProgressController;
use App\Http\Controllers\Progress\SpacedRepetitionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Welcome
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Rutas protegidas por Auth
Route::middleware(['auth', 'verified', 'streak'])->group(function () {
    
    // Onboarding
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/onboarding/vocational', [OnboardingController::class, 'submitVocationalTest'])->name('onboarding.vocational.submit');

    // Rutas protegidas por Onboarding
    Route::middleware(['onboarded'])->group(function () {
        
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Quiz
        Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
        Route::get('/quiz/{subject:slug}', [QuizController::class, 'show'])->name('quiz.show');

        // Simulator
        Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');
        Route::post('/simulator', [SimulatorController::class, 'store'])->name('simulator.store');
        Route::get('/simulator/{exam}', [SimulatorController::class, 'show'])->name('simulator.show');
        Route::post('/simulator/{exam}/submit', [SimulatorController::class, 'submit'])->name('simulator.submit');
        Route::get('/simulator/{exam}/results', [SimulatorController::class, 'results'])->name('simulator.results');

        // Progress & Review
        Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
        Route::get('/review', [SpacedRepetitionController::class, 'index'])->name('review.index');
    });

    // Profile (Inertia default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Panel
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
});

Route::get('/debug-nexus', function () {
    try {
        DB::connection()->getPdo();
        return "<h1>✅ Laravel está VIVO</h1><p>Base de datos: " . DB::connection()->getDatabaseName() . "</p><p>Vite Manifest: " . (file_exists(public_path('build/manifest.json')) ? 'Encontrado' : 'MISSING') . "</p>";
    } catch (\Exception $e) {
        return "<h1>❌ Error de Laravel</h1><p>" . $e->getMessage() . "</p>";
    }
});

require __DIR__.'/auth.php';
