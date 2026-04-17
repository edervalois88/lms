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
use App\Http\Controllers\Admin\AiMetricsController;
use App\Http\Controllers\CheckoutController;
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
    Route::post('/onboarding', [OnboardingController::class, 'store'])->middleware('throttle:10,1')->name('onboarding.store');
    Route::post('/onboarding/vocational', [OnboardingController::class, 'submitVocationalTest'])->middleware('throttle:10,1')->name('onboarding.vocational.submit');
    Route::get('/onboarding/diagnostic', [QuizController::class, 'onboardingDiagnostic'])->name('onboarding.diagnostic');
    Route::post('/onboarding/diagnostic/start', [QuizController::class, 'startOnboardingDiagnostic'])->middleware('throttle:10,1')->name('onboarding.diagnostic.start');

    // Checkout (Stripe)
    Route::post('/checkout', [CheckoutController::class, 'createSession'])->middleware('throttle:10,1')->name('checkout.create');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

    // Rutas protegidas por Onboarding
    Route::middleware(['onboarded'])->group(function () {
        
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Daily Practice (Duolingo-style daily loop)
        Route::get('/daily-practice', [DailyPracticeController::class, 'index'])->name('practice.daily');

        // Quiz
        Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
        Route::post('/quiz/bootcamp/start', [QuizController::class, 'startBootcamp'])->middleware('throttle:20,1')->name('quiz.bootcamp.start');
        Route::get('/quiz/{subject:slug}', [QuizController::class, 'show'])->name('quiz.show');
        Route::post('/quiz/{subject:slug}/question', [QuizController::class, 'question'])->middleware('throttle:50,1')->name('quiz.question');
        Route::post('/quiz/{subject:slug}/evaluate', [QuizController::class, 'evaluate'])->middleware('throttle:50,1')->name('quiz.evaluate');
        Route::post('/quiz/{subject:slug}/tutor', [QuizController::class, 'tutor'])->middleware('throttle:20,1')->name('quiz.tutor');

        // Simulator
        Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');
        Route::post('/simulator', [SimulatorController::class, 'store'])->middleware('throttle:10,1')->name('simulator.store');
        Route::get('/simulator/{exam}', [SimulatorController::class, 'show'])->name('simulator.show');
        Route::post('/simulator/{exam}/submit', [SimulatorController::class, 'submit'])->middleware('throttle:10,1')->name('simulator.submit');
        Route::get('/simulator/{exam}/results', [SimulatorController::class, 'results'])->name('simulator.results');
        Route::get('/simulator/{exam}/review', [SimulatorController::class, 'review'])->name('simulator.review');
        Route::post('/simulator/{exam}/review/tutor', [SimulatorController::class, 'reviewTutor'])->middleware('throttle:20,1')->name('simulator.review.tutor');

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
    Route::post('/rewards/purchase', [RewardStoreController::class, 'purchase'])->middleware('throttle:10,1')->name('rewards.purchase');
    Route::post('/rewards/equip', [RewardStoreController::class, 'equip'])->middleware('throttle:10,1')->name('rewards.equip');
    Route::get('/rewards/shop', [RewardStoreController::class, 'shopPage'])->name('rewards.shop');
});

// Admin Panel
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Main Dashboard
    Route::get('/', function () {
        return redirect(route('admin.dashboard'));
    })->name('admin.index');

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/ai-metrics', AiMetricsController::class)->name('admin.ai-metrics');

    // Users Management
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('admin.users.index');
    Route::get('/users/{user}', [AdminController::class, 'usersShow'])->name('admin.users.show');
    Route::put('/users/{user}', [AdminController::class, 'usersUpdate'])->name('admin.users.update');

    // Questions Management
    Route::get('/questions', [AdminController::class, 'questionsIndex'])->name('admin.questions.index');
    Route::get('/questions/{question}', [AdminController::class, 'questionsShow'])->name('admin.questions.show');
    Route::put('/questions/{question}', [AdminController::class, 'questionsUpdate'])->name('admin.questions.update');

    // Curation Panel
    Route::get('/curation', [AdminDashboardController::class, 'curationIndex'])->name('admin.curation.index');
    Route::get('/curation/search', [AdminDashboardController::class, 'searchQuestion'])->name('admin.curation.search');
    Route::put('/curation/questions/{id}', [AdminDashboardController::class, 'updateQuestionAndCache'])->name('admin.curation.update');

    // Analytics
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');

    // Settings
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
});

// Stripe Webhook (public)
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

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
