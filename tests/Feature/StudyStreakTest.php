<?php

use App\Models\User;
use App\Services\Learning\StudyStreakService;
use Carbon\Carbon;
use Database\Factories\UserFactory;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function freshUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'streak_days'    => 0,
        'last_study_at'  => null,
    ], $attrs));
}

// ---------------------------------------------------------------------------
// First activity ever → streak becomes 1
// ---------------------------------------------------------------------------
it('starts a streak of 1 on the first ever study activity', function () {
    $user    = freshUser();
    $service = app(StudyStreakService::class);

    $service->recordStudyActivity($user);

    expect($user->fresh()->streak_days)->toBe(1);
});

// ---------------------------------------------------------------------------
// Same day → streak stays the same (no double-count)
// ---------------------------------------------------------------------------
it('does not increment the streak if already recorded today', function () {
    $user = freshUser([
        'streak_days'   => 3,
        'last_study_at' => Carbon::today(),
    ]);
    $service = app(StudyStreakService::class);

    $service->recordStudyActivity($user);

    expect($user->fresh()->streak_days)->toBe(3);
});

// ---------------------------------------------------------------------------
// Yesterday → streak increments by 1
// ---------------------------------------------------------------------------
it('increments the streak when last activity was yesterday', function () {
    $user = freshUser([
        'streak_days'   => 5,
        'last_study_at' => Carbon::yesterday(),
    ]);
    $service = app(StudyStreakService::class);

    $service->recordStudyActivity($user);

    expect($user->fresh()->streak_days)->toBe(6);
});

// ---------------------------------------------------------------------------
// Missed day → streak resets to 1
// ---------------------------------------------------------------------------
it('resets the streak to 1 after a missed day', function () {
    $user = freshUser([
        'streak_days'   => 10,
        'last_study_at' => Carbon::today()->subDays(2),
    ]);
    $service = app(StudyStreakService::class);

    $service->recordStudyActivity($user);

    expect($user->fresh()->streak_days)->toBe(1);
});

// ---------------------------------------------------------------------------
// last_study_at is always updated to today
// ---------------------------------------------------------------------------
it('always updates last_study_at to today', function () {
    $user = freshUser([
        'last_study_at' => Carbon::yesterday(),
        'streak_days'   => 1,
    ]);
    $service = app(StudyStreakService::class);

    $service->recordStudyActivity($user);

    expect($user->fresh()->last_study_at->isToday())->toBeTrue();
});
