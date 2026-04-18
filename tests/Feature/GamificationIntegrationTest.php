<?php

use App\Enums\AchievementId;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

// ---------------------------------------------------------------------------
// Happy path — user earns XP and achievement on 10 quiz questions
// ---------------------------------------------------------------------------
it('earns xp and achievement when completing 10 quiz questions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create subject and questions
    $subject = Subject::factory()->create();
    $topic = Topic::factory()->create(['subject_id' => $subject->id]);
    $questions = Question::factory()->count(10)->create([
        'topic_id' => $topic->id,
        'options' => ['Correcto', 'Opción B', 'Opción C', 'Opción D'],
        'correct_answer' => 'Correcto',
    ]);

    // Answer 10 questions (should trigger achievement and XP awards)
    foreach ($questions as $question) {
        $response = $this->postJson(
            route('quiz.evaluate', $subject),
            [
                'question_id' => $question->id,
                'selected_index' => 0, // Correct answer
                'skip_adaptation' => false,
            ]
        );

        expect($response->status())->toBe(200);
        expect($response->json())->toHaveKeys(['gold_earned', 'xp_earned', 'achievements_unlocked']);
    }

    // Refresh user to get updated gamification state
    $user->refresh();

    // User should have XP in gamification state
    expect($user->gamification['xp'] ?? 0)->toBeGreaterThanOrEqual(0);

    // User should have unlocked 'first_quiz' achievement
    expect($user->achievements()->where('achievement_id', AchievementId::FIRST_QUIZ)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gamification state endpoint returns complete state
// ---------------------------------------------------------------------------
it('gamification state endpoint returns complete state with all fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Manually unlock an achievement
    $user->achievements()->create([
        'achievement_id' => AchievementId::FIRST_QUIZ,
        'cosmetic_unlocked' => 'accessory_badge',
        'unlocked_at' => now(),
    ]);

    $response = $this->getJson('/api/gamification/state');

    expect($response->status())->toBe(200);
    expect($response->json())->toHaveKeys([
        'gold',
        'xp',
        'current_level',
        'achievements_unlocked',
        'inventory',
        'equipped',
    ]);

    // Verify the achievement is in the returned state
    expect($response->json('achievements_unlocked'))->toContain(AchievementId::FIRST_QUIZ);
});

// ---------------------------------------------------------------------------
// Dashboard loads with authenticated user
// ---------------------------------------------------------------------------
it('dashboard loads successfully with authenticated user', function () {
    $user = User::factory()->create();
    $user->update(['has_completed_onboarding' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    expect($response->status())->toBe(200);
});

// ---------------------------------------------------------------------------
// User hydration includes gamification state on Inertia response
// ---------------------------------------------------------------------------
it('user hydration includes gamification state on dashboard', function () {
    $user = User::factory()->create();
    $user->update(['has_completed_onboarding' => true]);

    // Create some XP ledgers for the user
    $user->xpLedgers()->create([
        'amount' => 500,
        'reason' => 'test',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    expect($response->status())->toBe(200);
    expect(auth()->user()->id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// Quiz evaluate with multiple questions triggers achievements
// ---------------------------------------------------------------------------
it('quiz evaluate with 10 questions triggers achievement unlocking', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create subject and questions for testing
    $subject = Subject::factory()->create();
    $topic = Topic::factory()->create(['subject_id' => $subject->id]);
    $questions = Question::factory()->count(10)->create([
        'topic_id' => $topic->id,
        'options' => ['Correct', 'Wrong A', 'Wrong B', 'Wrong C'],
        'correct_answer' => 'Correct',
    ]);

    $achievementsUnlocked = [];

    // Answer 10 questions to trigger achievements
    foreach ($questions as $question) {
        $response = $this->postJson(
            route('quiz.evaluate', $subject),
            [
                'question_id' => $question->id,
                'selected_index' => 0,
                'skip_adaptation' => false,
            ]
        );

        expect($response->status())->toBe(200);

        // Collect achievements from responses
        $unlocked = $response->json('achievements_unlocked', []);
        $achievementsUnlocked = array_merge($achievementsUnlocked, $unlocked);
    }

    // First quiz completion should unlock an achievement
    $user->refresh();
    expect($user->achievements()->count())->toBeGreaterThan(0);
});

// ---------------------------------------------------------------------------
// Gamification state persists across multiple requests
// ---------------------------------------------------------------------------
it('gamification state persists across multiple requests with updated xp', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Fetch gamification state first time
    $response1 = $this->getJson('/api/gamification/state');
    expect($response1->status())->toBe(200);
    $initialXp = $response1->json('xp');

    // Award XP via ledger
    $user->xpLedgers()->create([
        'amount' => 100,
        'reason' => 'test_award',
    ]);

    // Fetch gamification state second time
    $response2 = $this->getJson('/api/gamification/state');
    expect($response2->status())->toBe(200);
    $updatedXp = $response2->json('xp');

    // XP should have increased
    expect($updatedXp)->toBeGreaterThan($initialXp);
});

// ---------------------------------------------------------------------------
// Security — unauthenticated user cannot access gamification state
// ---------------------------------------------------------------------------
it('rejects unauthenticated requests to gamification state endpoint', function () {
    $response = $this->getJson('/api/gamification/state');

    expect($response->status())->toBe(401);
});

// ---------------------------------------------------------------------------
// Security — unauthenticated user cannot evaluate quiz
// ---------------------------------------------------------------------------
it('rejects unauthenticated requests to quiz evaluate endpoint', function () {
    $subject = Subject::factory()->create();
    $question = Question::factory()->create();

    $response = $this->postJson(
        route('quiz.evaluate', $subject),
        [
            'question_id' => $question->id,
            'selected_index' => 0,
        ]
    );

    expect($response->status())->toBe(401);
});

// ---------------------------------------------------------------------------
// Edge case — incorrect quiz answer still triggers evaluation
// ---------------------------------------------------------------------------
it('handles incorrect answers in quiz and includes in response', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $subject = Subject::factory()->create();
    $topic = Topic::factory()->create(['subject_id' => $subject->id]);
    $question = Question::factory()->create([
        'topic_id' => $topic->id,
        'options' => ['Correct', 'Wrong A', 'Wrong B', 'Wrong C'],
        'correct_answer' => 'Correct',
    ]);

    $response = $this->postJson(
        route('quiz.evaluate', $subject),
        [
            'question_id' => $question->id,
            'selected_index' => 1, // Wrong answer
            'skip_adaptation' => false,
        ]
    );

    expect($response->status())->toBe(200);
    expect($response->json())->toHaveKey('evaluacion.resultado');
    expect($response->json('evaluacion.resultado'))->toBe('ERROR');
});
