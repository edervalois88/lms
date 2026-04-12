<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\UserTopicMastery;
use App\Services\Learning\ExamAreaResolver;
use App\Services\Learning\SpacedRepetitionService;
use Inertia\Inertia;
use Inertia\Response;

class DailyPracticeController extends Controller
{
    private const DAILY_LIMIT = 10;
    private const SRS_SLOTS   = 5;
    private const WEAK_SLOTS  = 5;

    public function __construct(
        protected SpacedRepetitionService $srs,
        protected ExamAreaResolver $areaResolver,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        // 1. SRS due cards (up to 5) — already-scheduled review questions
        $dueCards   = $this->srs->getDueCards($user, self::SRS_SLOTS);
        $srsQuestions = $dueCards->map(fn($card) => $card->question)->filter();

        $srsQuestionIds = $srsQuestions->pluck('id');

        // 2. Weak topic questions — topics with low mastery or never practiced
        $weakQuestions = $this->loadWeakQuestions($user, $srsQuestionIds);

        // Merge and cap at DAILY_LIMIT
        $questions = $srsQuestions
            ->merge($weakQuestions)
            ->unique('id')
            ->take(self::DAILY_LIMIT)
            ->values();

        request()->session()->put('daily_practice.question_ids', $questions->pluck('id')->all());

        return Inertia::render('Student/DailyPractice', [
            'questions'  => $questions,
            'total'      => $questions->count(),
            'srs_count'  => $srsQuestions->count(),
        ]);
    }

    private function loadWeakQuestions($user, $excludeIds)
    {
        $needed = self::WEAK_SLOTS;

        // Topics where the user has low mastery (< 50 %)
        $weakTopicIds = UserTopicMastery::where('user_id', $user->id)
            ->where('mastery_score', '<', 0.5)
            ->orderBy('mastery_score')
            ->pluck('topic_id');

        $questions = collect();

        if ($weakTopicIds->isNotEmpty()) {
            $questions = Question::whereIn('topic_id', $weakTopicIds)
                ->whereNotIn('id', $excludeIds)
                ->where('is_active', true)
                ->with('topic')
                ->inRandomOrder()
                ->limit($needed)
                ->get();
        }

        // If not enough weak questions, fill with any questions from user's major area
        if ($questions->count() < $needed) {
            $remaining = $needed - $questions->count();
            $filledIds = $excludeIds->merge($questions->pluck('id'));

            $majorArea = $this->areaResolver->fromUser($user);

            $extra = Question::when($majorArea, function ($q) use ($majorArea) {
                    $q->whereHas('topic.subject', function ($s) use ($majorArea) {
                        $s->whereJsonContains('exam_areas', $majorArea);
                    });
                })
                ->whereNotIn('id', $filledIds)
                ->where('is_active', true)
                ->with('topic')
                ->inRandomOrder()
                ->limit($remaining)
                ->get();

            $questions = $questions->merge($extra);
        }

        return $questions;
    }
}
