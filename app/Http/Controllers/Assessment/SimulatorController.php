<?php

namespace App\Http\Controllers\Assessment;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Enums\SubjectArea;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SubmitExamRequest;
use App\Jobs\GenerateQuestionBatch;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\GroqService;
use App\Services\FreemiumLimitService;
use App\Services\Learning\AchievementService;
use App\Services\Learning\GamificationService;
use App\Services\Learning\StudyStreakService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SimulatorController extends Controller
{
    public function __construct(
        protected StudyStreakService $streakService,
        protected GamificationService $gamification,
        protected AchievementService $achievementService,
        protected GroqService $groq,
        protected FreemiumLimitService $freemium,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Simulator/Setup', [
            'areas' => collect(SubjectArea::cases())->map(fn($area) => [
                'value' => $area->value,
                'label' => "Área {$area->value}: {$area->label()}",
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'area' => 'required|integer|between:1,4',
            'type' => 'required|in:diagnostic,practice,simulation',
        ]);

        if ($request->input('type') === 'simulation') {
            $this->freemium->assertCanStartSimulation($request->user());
        }

        $config = [
            'diagnostic' => ['questions' => 30,  'minutes' => 45, 'type' => ExamType::Diagnostic],
            'practice'   => ['questions' => 60,  'minutes' => 90, 'type' => ExamType::Practice],
            'simulation' => ['questions' => 120, 'minutes' => 180, 'type' => ExamType::Simulation],
        ];

        $exam = Exam::create([
            'user_id'            => auth()->id(),
            'type'               => $config[$request->type]['type'],
            'exam_area'          => $request->area,
            'total_questions'    => $config[$request->type]['questions'],
            'time_limit_minutes' => $config[$request->type]['minutes'],
            'status'             => ExamStatus::InProgress,
            'started_at'         => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => route('simulator.show', $exam),
            ]);
        }

        return redirect()->route('simulator.show', $exam);
    }

    public function show(Exam $exam): Response|RedirectResponse
    {
        abort_if($exam->user_id !== auth()->id(), 403);

        // Reusar el set de preguntas si el examen ya fue cargado antes.
        if ($exam->questions()->exists()) {
            $questions = $exam->questions()->with('topic.subject')->get();
        } else {
            $questions = $this->buildRealExamQuestionSet($exam->exam_area, $exam->total_questions);

            if ($questions->isEmpty()) {
                return redirect()
                    ->route('simulator.index')
                    ->with('error', 'No hay preguntas suficientes para esta area en este momento.');
            }

            if ($exam->type === ExamType::Simulation && $questions->count() < 120) {
                return redirect()
                    ->route('simulator.index')
                    ->with('error', 'No hay 120 preguntas disponibles para el modo Simulacro Estricto. Intenta más tarde.');
            }

            if ($questions->count() < $exam->total_questions) {
                $this->dispatchQuestionReplenishment($exam, $exam->total_questions - $questions->count());
                $exam->update(['total_questions' => $questions->count()]);
            }

            $exam->questions()->syncWithoutDetaching($questions->pluck('id')->all());
        }

        // Randomize options for each question
        $questions->each(fn($q) => $q->randomizeOptions());

        return Inertia::render('Simulator/Exam', [
            'exam' => $exam,
            'questions' => $questions
        ]);
    }

    public function submit(Exam $exam, SubmitExamRequest $request): RedirectResponse
    {
        abort_if($exam->user_id !== auth()->id(), 403);

        if ($exam->status !== ExamStatus::InProgress) {
            throw ValidationException::withMessages([
                'exam' => 'Este simulador ya fue enviado o no esta en progreso.',
            ]);
        }

        $deadline = Carbon::parse($exam->started_at)->addMinutes((int) $exam->time_limit_minutes);
        if (now()->greaterThan($deadline)) {
            throw ValidationException::withMessages([
                'exam' => 'El tiempo del simulador ha expirado. Inicia un nuevo intento.',
            ]);
        }

        $submittedAnswers = $request->input('answers', []);

        if (count($submittedAnswers) > $exam->total_questions) {
            throw ValidationException::withMessages([
                'answers' => 'Se recibieron mas respuestas que preguntas del simulador.',
            ]);
        }

        $questionIds = collect($submittedAnswers)->pluck('question_id')->unique()->values()->all();

        $examQuestionIds = $exam->questions()->pluck('questions.id')->all();
        $invalidQuestionIds = array_diff($questionIds, $examQuestionIds);

        if (!empty($invalidQuestionIds)) {
            throw ValidationException::withMessages([
                'answers' => 'El envio incluye preguntas que no pertenecen a este simulador.',
            ]);
        }
        
        DB::transaction(function () use ($exam, $submittedAnswers) {
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->id);

            if ($lockedExam->status !== ExamStatus::InProgress) {
                throw ValidationException::withMessages([
                    'exam' => 'Este simulador ya fue enviado.',
                ]);
            }

            $correctCount = 0;
            
            // Cargar preguntas para verificar en batch
            $questionIds = collect($submittedAnswers)->pluck('question_id')->toArray();
            $questions = $lockedExam->questions()->whereIn('questions.id', $questionIds)->get()->keyBy('id');

            foreach ($submittedAnswers as $answerData) {
                $question = $questions->get($answerData['question_id']);
                
                // Recalcular is_correct en el servidor (Seguridad)
                $isCorrect = false;
                if ($question) {
                    $correctOption = $question->correct_answer;
                    $selectedOption = $question->options[$answerData['selected_index']] ?? null;
                    $isCorrect = ($selectedOption === $correctOption);
                }

                if ($isCorrect) $correctCount++;

                ExamAnswer::create([
                    'exam_id' => $lockedExam->id,
                    'question_id' => $answerData['question_id'],
                    'user_answer' => $answerData['selected_index'],
                    'is_correct' => $isCorrect,
                    'time_spent_seconds' => min((int) ($answerData['time_spent'] ?? 0), 7200),
                ]);
            }

            $lockedExam->update([
                'status' => ExamStatus::Completed,
                'completed_at' => now(),
                'score' => $correctCount,
            ]);

            $this->streakService->recordStudyActivity($lockedExam->user);

            // Calculate score percentage for achievement evaluation
            $scorePercentage = $lockedExam->total_questions > 0
                ? (int) (($correctCount / $lockedExam->total_questions) * 100)
                : 0;

            // Evaluate achievements based on exam completion
            $achievementsUnlocked = $this->achievementService->evaluateAchievements(
                $lockedExam->user,
                'simulator_complete',
                [
                    'score' => $scorePercentage,
                    'questions_answered' => count($submittedAnswers),
                ]
            );

            if ($lockedExam->type === ExamType::Simulation) {
                $this->gamification->awardSimulationCompletion($lockedExam->user, (int) $lockedExam->id);
            } elseif ($lockedExam->type === ExamType::Practice && (int) $lockedExam->exam_area === 0) {
                $this->gamification->awardAdaptiveBootcampCompletion($lockedExam->user, (int) $lockedExam->id);
            }
        });

        return redirect()->route('simulator.results', $exam);
    }

    public function results(Exam $exam): Response
    {
        abort_if($exam->user_id !== auth()->id(), 403);
        abort_if($exam->status !== ExamStatus::Completed, 403, 'Este simulacro aún no está completado.');

        $user = auth()->user()->load('major');
        $total      = $exam->total_questions;
        $correct    = (int) $exam->score;
        $percentage = $total > 0 ? round(($correct / $total) * 100) : 0;

        $questions = $exam->questions()->with('topic.subject')->get()->keyBy('id');
        $answers = $exam->examAnswers()->get()->keyBy('question_id');

        $subjectBreakdown = [];
        $incorrectAnswersCount = 0;

        foreach ($questions as $questionId => $question) {
            $subjectName = (string) ($question->topic?->subject?->name ?? 'General');

            if (! isset($subjectBreakdown[$subjectName])) {
                $subjectBreakdown[$subjectName] = [
                    'subject' => $subjectName,
                    'correct' => 0,
                    'total' => 0,
                    'accuracy' => 0,
                    'status' => 'opportunity',
                ];
            }

            $subjectBreakdown[$subjectName]['total']++;

            $answer = $answers->get($questionId);
            $isCorrect = (bool) ($answer?->is_correct ?? false);

            if ($isCorrect) {
                $subjectBreakdown[$subjectName]['correct']++;
            } else {
                $incorrectAnswersCount++;
            }
        }

        $subjectBreakdown = collect($subjectBreakdown)
            ->map(function (array $row) {
                $accuracy = $row['total'] > 0 ? (int) round(($row['correct'] / $row['total']) * 100) : 0;
                $row['accuracy'] = $accuracy;
                $row['status'] = $accuracy >= 70 ? 'mastered' : 'opportunity';

                return $row;
            })
            ->values();

        $aiService = app(\App\Services\AI\GroqService::class);
        $suggestions = [];
        $aiOpportunities = [
            'critical_areas' => [],
            'strengths' => [],
            'study_plan' => null,
            'motivational_message' => null,
        ];

        $opportunitySubjects = $subjectBreakdown
            ->filter(fn (array $row) => ($row['status'] ?? 'opportunity') === 'opportunity')
            ->sortBy('accuracy')
            ->values();

        $fallbackCriticalAreas = $opportunitySubjects
            ->pluck('subject')
            ->take(4)
            ->values()
            ->all();

        $fallbackStrengths = $subjectBreakdown
            ->filter(fn (array $row) => ($row['status'] ?? '') === 'mastered')
            ->sortByDesc('accuracy')
            ->pluck('subject')
            ->take(3)
            ->values()
            ->all();

        $aiProfile = $aiService->analyzeProfile([
            'exam_id' => (int) $exam->id,
            'exam_type' => (string) $exam->type->value,
            'goal_major' => $user->major?->name,
            'goal_min_score' => $user->major?->min_score,
            'score' => (int) $correct,
            'total' => (int) $total,
            'percentage' => (int) $percentage,
            'incorrect_answers_count' => (int) $incorrectAnswersCount,
            'subject_breakdown' => $subjectBreakdown->toArray(),
            'priority_subjects' => $fallbackCriticalAreas,
        ]);

        if (is_array($aiProfile)) {
            $aiOpportunities['critical_areas'] = collect((array) ($aiProfile['critical_areas'] ?? []))
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn ($value) => trim($value))
                ->take(4)
                ->values()
                ->all();

            $aiOpportunities['strengths'] = collect((array) ($aiProfile['strengths'] ?? []))
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn ($value) => trim($value))
                ->take(3)
                ->values()
                ->all();

            $studyPlan = $aiProfile['study_plan'] ?? null;
            $motivation = $aiProfile['motivational_message'] ?? null;

            $aiOpportunities['study_plan'] = is_string($studyPlan) && trim($studyPlan) !== '' ? trim($studyPlan) : null;
            $aiOpportunities['motivational_message'] = is_string($motivation) && trim($motivation) !== '' ? trim($motivation) : null;
        }

        if (empty($aiOpportunities['critical_areas'])) {
            $aiOpportunities['critical_areas'] = $fallbackCriticalAreas;
        }

        if (empty($aiOpportunities['strengths'])) {
            $aiOpportunities['strengths'] = $fallbackStrengths;
        }
        
        if ($user->major && $correct < $user->major->min_score) {
            $suggestions = $aiService->suggestAlternatives($user->major->name, $user->major->min_score, $correct);
        }

        $message = match(true) {
            $percentage >= 80 => '¡Excelente! Estás listo para el examen real.',
            $percentage >= 60 => '¡Muy bien! Sigue practicando para mejorar.',
            $percentage >= 40 => 'Vas por buen camino. Refuerza tus áreas débiles.',
            default           => '¡No te rindas! Cada simulacro te hace más fuerte.',
        };

        $xpAwarded = 0;
        if ($exam->type === ExamType::Simulation) {
            $xpAwarded = GamificationService::XP_SIMULATION_COMPLETE;
        } elseif ($exam->type === ExamType::Practice && (int) $exam->exam_area === 0) {
            $xpAwarded = GamificationService::XP_BOOTCAMP_ADAPTIVE;
        }

        // Evaluate achievements based on exam results
        $achievementsUnlocked = $this->achievementService->evaluateAchievements(
            $user,
            'simulator_complete',
            [
                'score' => $percentage,
                'questions_answered' => $exam->total_questions ?? 0,
            ]
        );

        return Inertia::render('Simulator/Results', [
            'exam'       => $exam,
            'correct'    => $correct,
            'total'      => $total,
            'percentage' => $percentage,
            'message'    => $message,
            'goal'       => $user->major,
            'ai_suggestions' => $suggestions,
            'ai_opportunities' => $aiOpportunities,
            'xp_earned' => $xpAwarded,
            'gold_earned' => $xpAwarded,
            'achievements_unlocked' => $achievementsUnlocked,
            'subject_breakdown' => $subjectBreakdown,
            'incorrect_answers_count' => $incorrectAnswersCount,
        ]);
    }

    public function review(Exam $exam): Response
    {
        abort_if($exam->user_id !== auth()->id(), 403);

        $exam->load([
            'examAnswers.question.topic.subject',
            'questions.topic.subject',
        ]);

        $answersByQuestionId = $exam->examAnswers->keyBy('question_id');

        $incorrectQuestions = $exam->questions
            ->map(function ($question) use ($answersByQuestionId) {
                $answer = $answersByQuestionId->get($question->id);
                $isCorrect = (bool) ($answer?->is_correct ?? false);

                if ($isCorrect) {
                    return null;
                }

                $selectedRaw = $answer?->user_answer;
                $selectedIndex = null;

                if (is_numeric($selectedRaw)) {
                    $selectedIndex = (int) $selectedRaw;
                } elseif (is_string($selectedRaw) && $selectedRaw !== '') {
                    $idx = array_search($selectedRaw, (array) $question->options, true);
                    $selectedIndex = $idx === false ? null : (int) $idx;
                }

                return [
                    'id' => (int) $question->id,
                    'body' => (string) $question->body,
                    'options' => (array) $question->options,
                    'correct_index' => (int) $question->correct_index,
                    'correct_answer' => (string) $question->correct_answer,
                    'selected_index' => $selectedIndex,
                    'selected_answer' => $selectedIndex !== null ? ((array) $question->options)[$selectedIndex] ?? null : null,
                    'explanation' => (string) ($question->explanation ?? ''),
                    'topic_name' => (string) ($question->topic?->name ?? ''),
                    'subject_name' => (string) ($question->topic?->subject?->name ?? 'General'),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Simulator/Review', [
            'exam' => $exam,
            'incorrect_questions' => $incorrectQuestions,
        ]);
    }

    public function reviewTutor(Exam $exam, Request $request): JsonResponse
    {
        abort_if($exam->user_id !== auth()->id(), 403);

        $user = $request->user();
        $maxAttempts = (int) config('services.groq.tutor_rate_limit_per_minute', 8);
        $rateKey = sprintf('tutor:simulator:%d', (int) $user->id);

        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            return response()->json([
                'message' => 'Has alcanzado el límite de consultas al Tutor IA por minuto. Intenta de nuevo en unos segundos.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);

        $this->freemium->assertCanUseAiTutor($user);
        $this->freemium->registerAiTutorUsage($user);

        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'texto_duda' => ['nullable', 'string', 'max:1000'],
        ]);

        $question = $exam->questions()
            ->where('questions.id', (int) $data['question_id'])
            ->with('topic.subject')
            ->first();

        if (! $question) {
            return response()->json([
                'message' => 'La pregunta no pertenece a este simulacro.',
            ], 422);
        }

        $answer = ExamAnswer::query()
            ->where('exam_id', $exam->id)
            ->where('question_id', $question->id)
            ->first();

        $selectedRaw = (string) ($answer?->user_answer ?? '');
        $selectedAnswer = '';

        if ($selectedRaw !== '' && is_numeric($selectedRaw)) {
            $selectedAnswer = (string) (((array) $question->options)[(int) $selectedRaw] ?? '');
        } elseif ($selectedRaw !== '') {
            $selectedAnswer = $selectedRaw;
        }

        $tutor = $this->groq->getTutorExplanation(
            $question,
            $selectedAnswer,
            (string) ($data['texto_duda'] ?? ''),
        );

        if (! is_array($tutor)) {
            $fallback = trim((string) ($question->explanation ?? ''));
            $tutor = [
                'respuesta_directa' => $fallback !== ''
                    ? ('Respuesta correcta: **' . (string) $question->correct_answer . '**. ' . $fallback)
                    : ('Respuesta correcta: **' . (string) $question->correct_answer . '**. Revisa el concepto central del reactivo y por que los distractores son incorrectos.'),
                'es_fuera_de_contexto' => false,
                'from_cache' => false,
                'tokens_saved' => 0,
            ];
        }

        return response()->json([
            'chat' => [
                'respuesta_directa' => (string) ($tutor['respuesta_directa'] ?? ''),
                'es_fuera_de_contexto' => (bool) ($tutor['es_fuera_de_contexto'] ?? false),
                'from_cache' => (bool) ($tutor['from_cache'] ?? false),
                'tokens_saved' => (int) ($tutor['tokens_saved'] ?? 0),
            ],
        ]);
    }

    private function dispatchQuestionReplenishment(Exam $exam, int $missingCount): void
    {
        if ($missingCount <= 0) {
            return;
        }

        $topics = Topic::query()
            ->whereHas('subject', function ($query) use ($exam) {
                $query->whereJsonContains('exam_areas', $exam->exam_area);
            })
            ->inRandomOrder()
            ->limit(3)
            ->get();

        if ($topics->isEmpty()) {
            return;
        }

        $batchSize = max(1, (int) ceil($missingCount / $topics->count()));

        foreach ($topics as $topic) {
            try {
                GenerateQuestionBatch::dispatch(
                    $exam->user,
                    $topic,
                    random_int(4, 7),
                    $batchSize
                );
            } catch (\Throwable $exception) {
                // Fail-open: never block the simulator if queue infrastructure is unavailable.
                Log::warning('Question replenishment dispatch failed; continuing without queue.', [
                    'exam_id' => $exam->id,
                    'topic_id' => $topic->id,
                    'missing_count' => $missingCount,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function buildRealExamQuestionSet(int $area, int $totalQuestions)
    {
        $subjects = Subject::query()->byArea($area)->get()->keyBy('name');
        $blueprint = $this->scaledBlueprint($area, $totalQuestions);

        $selected = collect();

        foreach ($blueprint as $subjectName => $quota) {
            if ($quota <= 0) {
                continue;
            }

            $subject = $subjects->get($subjectName);
            if (! $subject) {
                continue;
            }

            $slice = Question::query()
                ->where('is_active', true)
                ->whereHas('topic', function ($query) use ($subject) {
                    $query->where('subject_id', $subject->id);
                })
                ->whereNotIn('id', $selected->pluck('id'))
                ->inRandomOrder()
                ->limit($quota)
                ->get();

            $selected = $selected->merge($slice);
        }

        if ($selected->count() < $totalQuestions) {
            $remaining = $totalQuestions - $selected->count();
            $subjectIds = $subjects->pluck('id')->all();

            $fill = Question::query()
                ->where('is_active', true)
                ->whereHas('topic', function ($query) use ($subjectIds) {
                    $query->whereIn('subject_id', $subjectIds);
                })
                ->whereNotIn('id', $selected->pluck('id'))
                ->inRandomOrder()
                ->limit($remaining)
                ->get();

            $selected = $selected->merge($fill);
        }

        return $selected->take($totalQuestions)->values();
    }

    private function scaledBlueprint(int $area, int $totalQuestions): array
    {
        $base = $this->blueprintByArea($area);
        $baseTotal = max(1, array_sum($base));

        $scaled = [];
        $fractions = [];

        foreach ($base as $subject => $count) {
            $exact = ($count / $baseTotal) * $totalQuestions;
            $scaled[$subject] = (int) floor($exact);
            $fractions[$subject] = $exact - $scaled[$subject];
        }

        $assigned = array_sum($scaled);
        $left = $totalQuestions - $assigned;

        if ($left > 0) {
            arsort($fractions);
            foreach (array_keys($fractions) as $subject) {
                if ($left <= 0) {
                    break;
                }

                $scaled[$subject]++;
                $left--;
            }
        }

        return $scaled;
    }

    private function blueprintByArea(int $area): array
    {
        return match ($area) {
            // Blueprint base de 120 reactivos (estilo UNAM)
            1 => [
                'Español' => 18,
                'Literatura' => 10,
                'Matemáticas' => 26,
                'Física' => 24,
                'Química' => 10,
                'Biología' => 10,
                'Historia Universal' => 10,
                'Historia de México' => 10,
                'Geografía' => 2,
            ],
            2 => [
                'Español' => 18,
                'Literatura' => 10,
                'Matemáticas' => 22,
                'Física' => 18,
                'Química' => 22,
                'Biología' => 22,
                'Historia Universal' => 10,
                'Historia de México' => 10,
                'Geografía' => 8,
            ],
            3 => [
                'Español' => 24,
                'Literatura' => 12,
                'Matemáticas' => 14,
                'Física' => 8,
                'Química' => 8,
                'Biología' => 8,
                'Historia Universal' => 18,
                'Historia de México' => 18,
                'Geografía' => 10,
            ],
            default => [
                'Español' => 24,
                'Literatura' => 20,
                'Matemáticas' => 12,
                'Física' => 6,
                'Química' => 6,
                'Biología' => 8,
                'Historia Universal' => 18,
                'Historia de México' => 18,
                'Geografía' => 8,
            ],
        };
    }
}
