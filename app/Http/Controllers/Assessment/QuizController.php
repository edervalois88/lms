<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\AI\QuestionGeneratorService;
use App\Services\Learning\AdaptiveDifficultyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(
        protected AdaptiveDifficultyService $difficultyService,
        protected QuestionGeneratorService $questionGenerator,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        // Obtener área del último examen o por defecto Área 1
        $area = $user->exams()->latest()->value('exam_area') ?? 1;

        $subjects = Subject::byArea($area)
            ->withCount('topics')
            ->get()
            ->map(function ($subject) use ($user) {
                return array_merge($subject->toArray(), [
                    'current_difficulty' => $this->difficultyService->getCurrentDifficulty($user, $subject)
                ]);
            });

        return Inertia::render('Quiz/Index', [
            'subjects' => $subjects
        ]);
    }

    public function show(Subject $subject): Response
    {
        $user = auth()->user();
        $topics = $subject->topics()
            ->withCount('questions')
            ->get();

        return Inertia::render('Quiz/Session', [
            'subject' => $subject,
            'topics' => $topics,
            'initial_difficulty' => $this->difficultyService->getCurrentDifficulty($user, $subject)
        ]);
    }

    public function question(Request $request, Subject $subject): JsonResponse
    {
        $data = $request->validate([
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
        ]);

        $user = auth()->user();

        $topic = Topic::query()
            ->where('subject_id', $subject->id)
            ->findOrFail($data['topic_id']);

        $difficulty = $this->difficultyService->getCurrentDifficulty($user, $subject);

        $question = Question::query()
            ->where('topic_id', $topic->id)
            ->where('is_active', true)
            ->whereBetween('difficulty', [max(1, $difficulty - 1), min(10, $difficulty + 1)])
            ->inRandomOrder()
            ->first();

        if (! $question) {
            $question = Question::query()
                ->where('topic_id', $topic->id)
                ->where('is_active', true)
                ->inRandomOrder()
                ->first();
        }

        if (! $question) {
            $question = $this->questionGenerator->generateForTopic($topic, $difficulty, $user);
        }

        if (! $question) {
            return response()->json([
                'message' => 'No fue posible generar una pregunta en este momento. Intenta de nuevo.',
            ], 422);
        }

        $question->load('topic.subject');

        return response()->json([
            'id' => $question->id,
            'body' => $question->body,
            'options' => $question->options,
            'correct_index' => $question->correct_index,
            'explanation' => $question->explanation,
            'concept' => $question->topic?->name,
            'topic_detail' => $question->topic?->description,
            'subject_name' => $question->topic?->subject?->name,
        ]);
    }
}
