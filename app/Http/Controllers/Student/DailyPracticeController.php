<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Topic;
use App\Models\UserTopicMastery;
use App\Services\AI\GroqService;
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
        protected GroqService $groq,
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
        $weakMasteries = UserTopicMastery::where('user_id', $user->id)
            ->where('mastery_score', '<', 0.5)
            ->orderBy('mastery_score')
            ->limit(30)
            ->get(['topic_id', 'mastery_score']);

        $weakTopicIds = $weakMasteries->pluck('topic_id');

        $questions = collect();

        if ($weakTopicIds->isNotEmpty()) {
            $topics = Topic::query()
                ->whereIn('id', $weakTopicIds)
                ->with('subject:id,name')
                ->get(['id', 'subject_id', 'name'])
                ->keyBy('id');

            $masteryByTopicId = $weakMasteries
                ->mapWithKeys(fn ($row) => [(int) $row->topic_id => (float) $row->mastery_score]);

            $topicPayload = $topics
                ->map(function ($topic) use ($masteryByTopicId) {
                    return [
                        'topic' => (string) $topic->name,
                        'subject' => (string) ($topic->subject?->name ?? 'General'),
                        'mastery_score' => round((float) ($masteryByTopicId[(int) $topic->id] ?? 0), 3),
                    ];
                })
                ->values()
                ->all();

            $priorityTopics = $this->groq->recommendWeakTopicPriorities($topicPayload, $user->major?->name);

            $priorityOrder = collect($priorityTopics)
                ->mapWithKeys(fn ($name, $index) => [mb_strtolower(trim((string) $name)) => $index]);

            $topicPriorityById = $topics->mapWithKeys(function ($topic) use ($priorityOrder, $masteryByTopicId) {
                $topicName = mb_strtolower(trim((string) $topic->name));
                $aiRank = $priorityOrder[$topicName] ?? 1000;
                $mastery = (float) ($masteryByTopicId[(int) $topic->id] ?? 1.0);

                // Lower rank first (AI), then lower mastery first.
                return [(int) $topic->id => $aiRank + $mastery];
            });

            $questions = Question::whereIn('topic_id', $weakTopicIds)
                ->whereNotIn('id', $excludeIds)
                ->where('is_active', true)
                ->with('topic')
                ->inRandomOrder()
                ->limit(max(20, $needed * 6))
                ->get()
                ->sortBy(function ($question) use ($topicPriorityById) {
                    return (float) ($topicPriorityById[(int) $question->topic_id] ?? 2000);
                })
                ->take($needed)
                ->values();
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
