<?php

namespace App\Http\Controllers\Assessment;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Enums\SubjectArea;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SubmitExamRequest;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SimulatorController extends Controller
{
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

        // Obtener materias del área
        $subjects = Subject::whereJsonContains('exam_areas', $exam->exam_area)->pluck('id');
        
        // Obtener preguntas aleatorias balanceadas
        $questions = Question::whereHas('topic', function($q) use ($subjects) {
                $q->whereIn('subject_id', $subjects);
            })
            ->inRandomOrder()
            ->limit($exam->total_questions)
            ->get();

        if ($questions->count() < $exam->total_questions) {
            $questions = $this->getFallbackQuestions($exam->total_questions);
        }

        return Inertia::render('Simulator/Exam', [
            'exam' => $exam,
            'questions' => $questions
        ]);
    }

    public function submit(Exam $exam, SubmitExamRequest $request): RedirectResponse
    {
        abort_if($exam->user_id !== auth()->id(), 403);

        $submittedAnswers = $request->input('answers', []);
        
        DB::transaction(function () use ($exam, $submittedAnswers) {
            $correctCount = 0;
            
            // Cargar preguntas para verificar en batch
            $questionIds = collect($submittedAnswers)->pluck('question_id')->toArray();
            $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');

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
                    'exam_id' => $exam->id,
                    'question_id' => $answerData['question_id'],
                    'user_answer' => $answerData['selected_index'],
                    'is_correct' => $isCorrect,
                    'time_spent_seconds' => $answerData['time_spent'] ?? 0,
                ]);
            }

            $exam->update([
                'status' => ExamStatus::Completed,
                'completed_at' => now(),
                'score' => $correctCount,
            ]);
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

    private function getFallbackQuestions(int $count)
    {
        return collect(range(1, $count))->map(function($i) {
            return [
                'id' => $i,
                'stem' => "¿Pregunta de prueba #$i? (Carga temas en la DB para ver reales)",
                'options' => ['Opción A', 'Opción B', 'Opción C', 'Opción D'],
                'correct_answer' => 'Opción A',
                'difficulty' => 5
            ];
        });
    }
}
