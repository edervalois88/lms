<?php

use App\Http\Controllers\Student\OnboardingController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\DailyPracticeController;
use App\Http\Controllers\Assessment\QuizController;
use App\Http\Controllers\Assessment\SimulatorController;
use App\Http\Controllers\Progress\ProgressController;
use App\Http\Controllers\Progress\SpacedRepetitionController;
use App\Http\Controllers\Rewards\RewardStoreController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
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
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Onboarding
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/onboarding/vocational', [OnboardingController::class, 'submitVocationalTest'])->name('onboarding.vocational.submit');

    // Rutas protegidas por Onboarding
    Route::middleware(['onboarded'])->group(function () {
        
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Daily Practice (Duolingo-style daily loop)
        Route::get('/daily-practice', [DailyPracticeController::class, 'index'])->name('practice.daily');

        // Quiz
        Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
        Route::get('/quiz/{subject:slug}', [QuizController::class, 'show'])->name('quiz.show');
        Route::post('/quiz/{subject:slug}/question', [QuizController::class, 'question'])->name('quiz.question');
        Route::post('/quiz/{subject:slug}/evaluate', [QuizController::class, 'evaluate'])->name('quiz.evaluate');
        Route::post('/quiz/{subject:slug}/tutor', [QuizController::class, 'tutor'])->name('quiz.tutor');

        // Simulator
        Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');
        Route::post('/simulator', [SimulatorController::class, 'store'])->name('simulator.store');
        Route::get('/simulator/{exam}', [SimulatorController::class, 'show'])->name('simulator.show');
        Route::post('/simulator/{exam}/submit', [SimulatorController::class, 'submit'])->name('simulator.submit');
        Route::get('/simulator/{exam}/results', [SimulatorController::class, 'results'])->name('simulator.results');
        Route::get('/simulator/{exam}/review', [SimulatorController::class, 'review'])->name('simulator.review');
        Route::post('/simulator/{exam}/review/tutor', [SimulatorController::class, 'reviewTutor'])->name('simulator.review.tutor');

        // Progress & Review
        Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
        Route::get('/review', [SpacedRepetitionController::class, 'index'])->name('review.index');
        Route::post('/review/answer/{question}', [SpacedRepetitionController::class, 'answer'])->name('review.answer');
    });

    // Profile (Inertia default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rewards / Cosmetics
    Route::get('/rewards/catalog', [RewardStoreController::class, 'catalog'])->name('rewards.catalog');
    Route::get('/rewards/inventory', [RewardStoreController::class, 'inventory'])->name('rewards.inventory');
    Route::post('/rewards/purchase', [RewardStoreController::class, 'purchase'])->name('rewards.purchase');
    Route::post('/rewards/equip', [RewardStoreController::class, 'equip'])->name('rewards.equip');
});

// Admin Panel
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

if (app()->environment('local')) {
    Route::get('/debug-nexus', function () {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return "<h1>✅ Laravel está VIVO</h1><p>Base de datos: "
                . \Illuminate\Support\Facades\DB::connection()->getDatabaseName()
                . "</p><p>Vite Manifest: "
                . (file_exists(public_path('build/manifest.json')) ? 'Encontrado' : 'MISSING')
                . "</p>";
        } catch (\Exception $e) {
            return "<h1>❌ Error de Laravel</h1><p>" . $e->getMessage() . "</p>";
        }
    });
}

require __DIR__.'/auth.php';
