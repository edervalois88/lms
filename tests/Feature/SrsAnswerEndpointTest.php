<?php

use App\Models\Question;
use App\Models\SpacedRepetitionCard;
use App\Models\User;

it('accepts review answer for due card and awards xp in backend', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarded_at' => now(),
        'preferences' => [],
    ]);

    $question = Question::factory()->create();

    SpacedRepetitionCard::create([
        'user_id' => $user->id,
        'question_id' => $question->id,
        'ease_factor' => 2.5,
        'interval' => 1,
        'repetitions' => 1,
        'next_review_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)
        ->postJson(route('review.answer', $question), [
            'quality' => 5,
            'source' => 'review',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('xp_awarded', 8);

    $user->refresh();

    expect((int) ($user->preferences['xp'] ?? 0))->toBe(8);
});

it('rejects review answer when question is not due for the user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarded_at' => now(),
    ]);

    $question = Question::factory()->create();

    $this->actingAs($user)
        ->postJson(route('review.answer', $question), [
            'quality' => 5,
            'source' => 'review',
        ])
        ->assertForbidden();
});

it('accepts daily answer only for questions in the active session list and consumes it', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarded_at' => now(),
        'preferences' => [],
    ]);

    $question = Question::factory()->create();

    $this->actingAs($user)
        ->withSession(['daily_practice.question_ids' => [$question->id]])
        ->postJson(route('review.answer', $question), [
            'quality' => 5,
            'source' => 'daily',
        ])
        ->assertOk()
        ->assertJsonPath('xp_awarded', 12);

    $this->actingAs($user)
        ->withSession(['daily_practice.question_ids' => []])
        ->postJson(route('review.answer', $question), [
            'quality' => 5,
            'source' => 'daily',
        ])
        ->assertForbidden();
});
