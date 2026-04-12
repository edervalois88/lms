<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\GroqService;
use App\Services\AI\QuestionGeneratorService;
use App\Services\Learning\AdaptiveExamPipelineService;
use App\Services\Learning\AdaptiveDifficultyService;
use App\Services\Learning\GamificationService;
use Carbon\Carbon;
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
        protected GamificationService $gamification,
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
        $levelBefore = $this->gamification->getLevel($user);
        $examId = $this->resolvePracticeExamId($request, $user->id, $subject->id);

        $sessionKey = $this->streakSessionKey($subject, $topic);
        $streak = (int) $request->session()->get($sessionKey, 0);

        // Solo actualiza racha en evaluaciones reales; las dudas del chat no deben modificar adaptabilidad.
        if (! $skip) {
            $streak = $isCorrect ? ($streak + 1) : 0;
            $request->session()->put($sessionKey, $streak);
        }

        $this->recordPracticeAnswer($examId, $question, $selectedAnswer, $isCorrect);

        $payload = $this->adaptivePipeline->evaluate(
            $user,
            $subject,
            $question,
            (int) $data['selected_index'],
            $streak,
            null,
            ! $skip,
        );

        $gamificationPayload = [
            'xp_earned' => 0,
            'current_xp' => $levelBefore['xp'] ?? 0,
            'level_up' => false,
            'new_level' => $levelBefore['current'] ?? 1,
            'unlocked_badges' => [],
        ];

        if (! $skip) {
            $questionCounterKey = sprintf('quiz.practice.counter.%d.%d', (int) $user->id, (int) $subject->id);
            $questionCounter = (int) $request->session()->get($questionCounterKey, 0) + 1;
            $request->session()->put($questionCounterKey, $questionCounter);

            if ($questionCounter % 10 === 0) {
                $practiceAward = $this->gamification->awardPracticeCompletion($user, (int) $subject->id);
                $consistencyAward = $this->gamification->awardConsistencyBonusIfEligible($user);
                $rankEvaluation = $this->gamification->evaluateSubjectRank($user, (int) $subject->id);

                $levelAfter = $this->gamification->getLevel($user);

                $xpEarned = (int) ($practiceAward['earned'] ?? 0) + (int) ($consistencyAward['earned'] ?? 0);
                $xpEarned += ! empty($rankEvaluation['unlocked_badges']) ? GamificationService::XP_SUBJECT_RANK_UP : 0;

                $gamificationPayload = [
                    'xp_earned' => $xpEarned,
                    'current_xp' => (int) ($levelAfter['xp'] ?? 0),
                    'level_up' => (int) ($levelAfter['current'] ?? 1) > (int) ($levelBefore['current'] ?? 1),
                    'new_level' => (int) ($levelAfter['current'] ?? 1),
                    'unlocked_badges' => (array) ($rankEvaluation['unlocked_badges'] ?? []),
                    'subject_rank' => $rankEvaluation['rank'] ?? null,
                    'subject_accuracy' => $rankEvaluation['accuracy'] ?? null,
                ];
            }
        }

        data_set($payload, 'evaluacion.resultado', $isCorrect ? 'ACIERTO' : 'ERROR');
        data_set($payload, 'metadatos.racha_aciertos', $streak);
        data_set($payload, 'gamification', $gamificationPayload);

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
        $user = auth()->user();

        if ((int) $question->topic?->subject_id !== (int) $subject->id) {
            return response()->json(['message' => 'Pregunta no válida para la materia seleccionada.'], 422);
        }

        $xpCharge = $this->gamification->spendXp($user, GamificationService::XP_TUTOR_HINT_COST, 'ai_hint', [
            'question_id' => (int) $question->id,
            'subject_id' => (int) $subject->id,
        ]);

        if (! (bool) ($xpCharge['ok'] ?? false)) {
            return response()->json([
                'message' => 'XP insuficiente para usar una pista IA. Costo: ' . GamificationService::XP_TUTOR_HINT_COST . ' XP.',
                'gamification' => [
                    'xp_earned' => 0,
                    'current_xp' => (int) ($xpCharge['current_xp'] ?? 0),
                    'hint_cost' => GamificationService::XP_TUTOR_HINT_COST,
                ],
            ], 422);
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
                'respuesta_directa' => $this->buildTutorFallback($question, $selectedAnswer, $correctAnswer),
                'es_fuera_de_contexto' => false,
                'from_cache' => false,
                'tokens_saved' => 0,
            ];
        }

        return response()->json([
            'explicacion' => (string) ($tutor['respuesta_directa'] ?? ''),
            'from_cache' => (bool) ($tutor['from_cache'] ?? false),
            'tokens_saved' => (int) ($tutor['tokens_saved'] ?? 0),
            'gamification' => [
                'xp_earned' => 0,
                'xp_spent' => (int) ($xpCharge['spent'] ?? GamificationService::XP_TUTOR_HINT_COST),
                'current_xp' => (int) ($xpCharge['current_xp'] ?? 0),
                'hint_cost' => GamificationService::XP_TUTOR_HINT_COST,
            ],
            'chat' => [
                'respuesta_directa' => (string) ($tutor['respuesta_directa'] ?? ''),
                'es_fuera_de_contexto' => (bool) ($tutor['es_fuera_de_contexto'] ?? false),
                'from_cache' => (bool) ($tutor['from_cache'] ?? false),
                'tokens_saved' => (int) ($tutor['tokens_saved'] ?? 0),
            ],
        ]);
    }

    public function tutorHealth(): JsonResponse
    {
        return response()->json($this->groq->tutorHealthCheck());
    }

    public function startBootcamp(Request $request)
    {
        $user = $request->user();
        $exam = $this->adaptivePipeline->generateTargetedBootcamp($user);

        if (! $exam) {
            return back()->with('error', 'No fue posible crear el bootcamp táctico en este momento. Intenta nuevamente.');
        }

        return redirect()->route('simulator.show', $exam)->with('success', 'Bootcamp táctico generado: 10 reactivos personalizados.');
    }

    private function buildTutorFallback(Question $question, string $respuestaAlumno, string $respuestaCorrecta): string
    {
        $explicacion = trim((string) ($question->explanation ?? ''));

        if ($explicacion === '') {
            return 'La respuesta correcta es **' . $respuestaCorrecta . '**. Tu selección fue **' . ($respuestaAlumno !== '' ? $respuestaAlumno : 'sin respuesta') . '**; revisa el concepto principal del reactivo y compáralo con las opciones para identificar el distractor.';
        }

        return 'La respuesta correcta es **' . $respuestaCorrecta . '**. Tu selección fue **' . ($respuestaAlumno !== '' ? $respuestaAlumno : 'sin respuesta') . '**. **Explicación oficial:** ' . $explicacion;
    }

    private function resolvePracticeExamId(Request $request, int $userId, int $subjectId): int
    {
        $sessionKey = sprintf('quiz.practice.exam.%d.%d', $userId, $subjectId);
        $existingId = (int) $request->session()->get($sessionKey, 0);

        if ($existingId > 0 && Exam::query()->where('id', $existingId)->exists()) {
            return $existingId;
        }

        $exam = Exam::create([
            'user_id' => $userId,
            'type' => 'practice',
            'exam_area' => null,
            'total_questions' => 10,
            'time_limit_minutes' => 15,
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
            'score' => null,
        ]);

        $request->session()->put($sessionKey, (int) $exam->id);

        return (int) $exam->id;
    }

    private function recordPracticeAnswer(int $examId, Question $question, string $selectedAnswer, bool $isCorrect): void
    {
        ExamAnswer::create([
            'exam_id' => $examId,
            'question_id' => $question->id,
            'user_answer' => $selectedAnswer,
            'is_correct' => $isCorrect,
            'time_spent_seconds' => 0,
            'confidence' => null,
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
