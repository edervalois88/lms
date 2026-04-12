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
use App\Models\Topic;
use App\Services\Learning\StudyStreakService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SimulatorController extends Controller
{
    public function __construct(
        protected StudyStreakService $streakService
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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'area' => 'required|integer|between:1,4',
            'type' => 'required|in:diagnostic,practice,simulation',
        ]);

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

        return redirect()->route('simulator.show', $exam);
    }

    public function show(Exam $exam): Response
    {
        abort_if($exam->user_id !== auth()->id(), 403);

        // Reusar el set de preguntas si el examen ya fue cargado antes.
        if ($exam->questions()->exists()) {
            $questions = $exam->questions()->with('topic.subject')->get();
        } else {
            $subjects = Subject::whereJsonContains('exam_areas', $exam->exam_area)->pluck('id');

            $questions = Question::whereHas('topic', function ($q) use ($subjects) {
                    $q->whereIn('subject_id', $subjects);
                })
                ->inRandomOrder()
                ->limit($exam->total_questions)
                ->get();

            if ($questions->isEmpty()) {
                return redirect()
                    ->route('simulator.index')
                    ->with('error', 'No hay preguntas suficientes para esta area en este momento.');
            }

            if ($questions->count() < $exam->total_questions) {
                $this->dispatchQuestionReplenishment($exam, $exam->total_questions - $questions->count());
                $exam->update(['total_questions' => $questions->count()]);
            }

            $exam->questions()->syncWithoutDetaching($questions->pluck('id')->all());
        }

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
        });

        return redirect()->route('simulator.results', $exam);
    }

    public function results(Exam $exam): Response
    {
        $user = auth()->user()->load('major');
        $total      = $exam->total_questions;
        $correct    = $exam->score;
        $percentage = $total > 0 ? round(($correct / $total) * 100) : 0;

        $aiService = app(\App\Services\AI\ClaudeService::class);
        $suggestions = [];
        
        if ($user->major && $correct < $user->major->min_score) {
            $suggestions = $aiService->suggestAlternatives($user->major->name, $user->major->min_score, $correct);
        }

        $message = match(true) {
            $percentage >= 80 => '¡Excelente! Estás listo para el examen real.',
            $percentage >= 60 => '¡Muy bien! Sigue practicando para mejorar.',
            $percentage >= 40 => 'Vas por buen camino. Refuerza tus áreas débiles.',
            default           => '¡No te rindas! Cada simulacro te hace más fuerte.',
        };

        return Inertia::render('Simulator/Results', [
            'exam'       => $exam,
            'correct'    => $correct,
            'total'      => $total,
            'percentage' => $percentage,
            'message'    => $message,
            'goal'       => $user->major,
            'ai_suggestions' => $suggestions
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
            GenerateQuestionBatch::dispatch(
                $exam->user,
                $topic,
                random_int(4, 7),
                $batchSize
            );
        }
    }
}
