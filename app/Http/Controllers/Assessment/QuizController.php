<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\GroqService;
use App\Services\AI\QuestionGeneratorService;
use App\Services\Learning\AdaptiveExamPipelineService;
use App\Services\Learning\AdaptiveDifficultyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(
        protected AdaptiveDifficultyService $difficultyService,
        protected QuestionGeneratorService $questionGenerator,
        protected AdaptiveExamPipelineService $adaptivePipeline,
        protected GroqService $groq,
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
        $sessionStreak = (int) $request->session()->get($this->streakSessionKey($subject, $topic), 0);

        // Si hay 3 aciertos seguidos, primero intenta una pregunta de nivel superior.
        $question = $this->findQuestionForAdaptiveStep($topic->id, $difficulty, $sessionStreak >= 3);

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

    public function evaluate(Request $request, Subject $subject): JsonResponse
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_index' => ['required', 'integer', 'min:0', 'max:3'],
            'skip_adaptation' => ['nullable', 'boolean'],
        ]);

        $user = auth()->user();

        $question = Question::query()
            ->with('topic.subject')
            ->findOrFail($data['question_id']);

        if ((int) $question->topic?->subject_id !== (int) $subject->id) {
            return response()->json(['message' => 'Pregunta no válida para la materia seleccionada.'], 422);
        }

        $skip = (bool) ($data['skip_adaptation'] ?? false);
        $topic = $question->topic;
        $selected = (array) ($question->options ?? []);
        $selectedAnswer = (string) ($selected[(int) $data['selected_index']] ?? '');
        $correctAnswer = (string) ($question->correct_answer ?? '');
        $isCorrect = $selectedAnswer !== '' && $selectedAnswer === $correctAnswer;

        $sessionKey = $this->streakSessionKey($subject, $topic);
        $streak = (int) $request->session()->get($sessionKey, 0);

        // Solo actualiza racha en evaluaciones reales; las dudas del chat no deben modificar adaptabilidad.
        if (! $skip) {
            $streak = $isCorrect ? ($streak + 1) : 0;
            $request->session()->put($sessionKey, $streak);
        }

        $payload = $this->adaptivePipeline->evaluate(
            $user,
            $subject,
            $question,
            (int) $data['selected_index'],
            $streak,
            null,
            ! $skip,
        );

        data_set($payload, 'evaluacion.resultado', $isCorrect ? 'ACIERTO' : 'ERROR');
        data_set($payload, 'metadatos.racha_aciertos', $streak);

        return response()->json($payload);
    }

    public function tutor(Request $request, Subject $subject): JsonResponse
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_index' => ['required', 'integer', 'min:0', 'max:3'],
            'texto_duda' => ['nullable', 'string', 'max:1000'],
        ]);

        $question = Question::query()
            ->with('topic.subject')
            ->findOrFail($data['question_id']);

        if ((int) $question->topic?->subject_id !== (int) $subject->id) {
            return response()->json(['message' => 'Pregunta no válida para la materia seleccionada.'], 422);
        }

        $selected = (array) ($question->options ?? []);
        $selectedAnswer = (string) ($selected[(int) $data['selected_index']] ?? '');
        $correctAnswer = (string) ($question->correct_answer ?? '');

        $tutor = $this->groq->getTutorExplanation(
            $question,
            $selectedAnswer,
            (string) ($data['texto_duda'] ?? ''),
        );

        if (! is_array($tutor)) {
            $tutor = [
                'respuesta_directa' => 'No pude generar la explicación en este momento. Revisa la respuesta correcta y la explicación oficial del reactivo.',
                'es_fuera_de_contexto' => false,
            ];
        }

        return response()->json([
            'chat' => [
                'respuesta_directa' => (string) ($tutor['respuesta_directa'] ?? ''),
                'es_fuera_de_contexto' => (bool) ($tutor['es_fuera_de_contexto'] ?? false),
            ],
        ]);
    }

    private function streakSessionKey(Subject $subject, Topic $topic): string
    {
        return sprintf('quiz.streak.%d.%d.%d', (int) auth()->id(), (int) $subject->id, (int) $topic->id);
    }

    private function findQuestionForAdaptiveStep(int $topicId, int $difficulty, bool $shouldRaiseLevel): ?Question
    {
        $baseQuery = Question::query()
            ->where('topic_id', $topicId)
            ->where('is_active', true);

        if ($shouldRaiseLevel) {
            if (Schema::hasColumn('questions', 'level_id')) {
                $higher = (clone $baseQuery)
                    ->where('level_id', '>', $difficulty)
                    ->orderBy('level_id')
                    ->inRandomOrder()
                    ->first();

                if ($higher) {
                    return $higher;
                }
            } else {
                $higher = (clone $baseQuery)
                    ->where('difficulty', '>', $difficulty)
                    ->orderBy('difficulty')
                    ->inRandomOrder()
                    ->first();

                if ($higher) {
                    return $higher;
                }
            }
        }

        $window = (clone $baseQuery)
            ->whereBetween('difficulty', [max(1, $difficulty - 1), min(10, $difficulty + 1)])
            ->inRandomOrder()
            ->first();

        if ($window) {
            return $window;
        }

        return (clone $baseQuery)
            ->inRandomOrder()
            ->first();
    }
}
