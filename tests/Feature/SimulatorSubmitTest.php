<?php

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Database\Factories\ExamFactory;
use Database\Factories\QuestionFactory;
use Database\Factories\UserFactory;

// ---------------------------------------------------------------------------
// Happy path — valid submission redirects to results
// ---------------------------------------------------------------------------
it('submits exam with valid answers and redirects to results', function () {
    $user = User::factory()->create();
    $questions = Question::factory()->count(3)->create();

    $exam = Exam::factory()->inProgress()->create([
        'user_id'         => $user->id,
        'total_questions' => 3,
    ]);
    $exam->questions()->attach($questions->pluck('id'));

    $answers = $questions->map(fn($q) => [
        'question_id'    => $q->id,
        'selected_index' => 0,
        'time_spent'     => 30,
    ])->all();

    $this->actingAs($user)
        ->post(route('simulator.submit', $exam), ['answers' => $answers])
        ->assertRedirect(route('simulator.results', $exam));

    expect($exam->fresh()->status)->toBe(ExamStatus::Completed);
});

// ---------------------------------------------------------------------------
// Security — question not in the exam's pinned set must be rejected
// ---------------------------------------------------------------------------
it('rejects answers containing questions not in the exam set', function () {
    $user = User::factory()->create();
    $examQuestion  = Question::factory()->create();
    $foreignQuestion = Question::factory()->create();

    $exam = Exam::factory()->inProgress()->create([
        'user_id'         => $user->id,
        'total_questions' => 1,
    ]);
    $exam->questions()->attach($examQuestion->id);

    $answers = [
        ['question_id' => $foreignQuestion->id, 'selected_index' => 0, 'time_spent' => 10],
    ];

    $this->actingAs($user)
        ->post(route('simulator.submit', $exam), ['answers' => $answers])
        ->assertSessionHasErrors('answers');
});

// ---------------------------------------------------------------------------
// Security — another user cannot submit someone else's exam
// ---------------------------------------------------------------------------
it('forbids submitting an exam owned by another user', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $question = Question::factory()->create();

    $exam = Exam::factory()->inProgress()->create([
        'user_id'         => $owner->id,
        'total_questions' => 1,
    ]);
    $exam->questions()->attach($question->id);

    $answers = [
        ['question_id' => $question->id, 'selected_index' => 0, 'time_spent' => 10],
    ];

    $this->actingAs($attacker)
        ->post(route('simulator.submit', $exam), ['answers' => $answers])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Double submit — second submit on a completed exam must fail
// ---------------------------------------------------------------------------
it('prevents double submission of an already completed exam', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $exam = Exam::factory()->completed()->create([
        'user_id'         => $user->id,
        'total_questions' => 1,
    ]);
    $exam->questions()->attach($question->id);

    $answers = [
        ['question_id' => $question->id, 'selected_index' => 0, 'time_spent' => 10],
    ];

    $this->actingAs($user)
        ->post(route('simulator.submit', $exam), ['answers' => $answers])
        ->assertSessionHasErrors('exam');
});

// ---------------------------------------------------------------------------
// Time expiration — submit after time_limit_minutes elapsed must fail
// ---------------------------------------------------------------------------
it('rejects submission after the time limit has expired', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $exam = Exam::factory()->expired()->create([
        'user_id'         => $user->id,
        'total_questions' => 1,
    ]);
    $exam->questions()->attach($question->id);

    $answers = [
        ['question_id' => $question->id, 'selected_index' => 0, 'time_spent' => 10],
    ];

    $this->actingAs($user)
        ->post(route('simulator.submit', $exam), ['answers' => $answers])
        ->assertSessionHasErrors('exam');
});

// ---------------------------------------------------------------------------
// Correctness — score is computed server-side regardless of client input
// ---------------------------------------------------------------------------
it('computes score server-side ignoring client-reported correctness', function () {
    $user = User::factory()->create();
    $options = ['Correcto', 'Opción B', 'Opción C', 'Opción D'];
    $question = Question::factory()->create([
        'options'        => $options,
        'correct_answer' => 'Correcto', // index 0
    ]);

    $exam = Exam::factory()->inProgress()->create([
        'user_id'         => $user->id,
        'total_questions' => 1,
    ]);
    $exam->questions()->attach($question->id);

    // Client sends index 0 (correct)
    $answers = [
        ['question_id' => $question->id, 'selected_index' => 0, 'time_spent' => 15],
    ];

    $this->actingAs($user)
        ->post(route('simulator.submit', $exam), ['answers' => $answers]);

    expect($exam->fresh()->score)->toBe(1);
});
