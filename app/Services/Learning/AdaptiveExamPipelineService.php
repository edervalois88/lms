<?php

namespace App\Services\Learning;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\RAG\VectorSearchService;

class AdaptiveExamPipelineService
{
    private const BOOTCAMP_QUESTION_COUNT = 10;

    public function __construct(
        protected AdaptiveDifficultyService $difficultyService,
        protected VectorSearchService $vector,
    ) {}

    public function getBootcampRecommendation(User $user): array
    {
        $weakSubjects = $this->resolveWeakSubjects($user);

        return [
            'subjects' => $weakSubjects->map(fn (array $row) => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'accuracy' => $row['accuracy'] !== null ? (int) $row['accuracy'] : null,
            ])->values()->all(),
            'is_fallback' => (bool) $weakSubjects->contains(fn (array $row) => $row['accuracy'] === null),
            'question_count' => self::BOOTCAMP_QUESTION_COUNT,
        ];
    }

    public function generateTargetedBootcamp(User $user): ?Exam
    {
        $weakSubjects = $this->resolveWeakSubjects($user);
        $subjectIds = $weakSubjects->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($subjectIds) < 2) {
            return null;
        }

        $questions = Question::query()
            ->where('is_active', true)
            ->whereHas('topic', function ($query) use ($subjectIds) {
                $query->whereIn('subject_id', $subjectIds);
            })
            ->inRandomOrder()
            ->limit(self::BOOTCAMP_QUESTION_COUNT)
            ->get();

        if ($questions->count() < self::BOOTCAMP_QUESTION_COUNT) {
            return null;
        }

        $exam = Exam::create([
            'user_id' => $user->id,
            'type' => ExamType::Practice,
            // exam_area=0 marca este examen como bootcamp adaptativo
            'exam_area' => 0,
            'total_questions' => self::BOOTCAMP_QUESTION_COUNT,
            'time_limit_minutes' => 20,
            'status' => ExamStatus::InProgress,
            'started_at' => now(),
        ]);

        $exam->questions()->syncWithoutDetaching($questions->pluck('id')->all());

        return $exam;
    }

    public function evaluate(
        User $user,
        Subject $subject,
        Question $question,
        int $selectedIndex,
        int $correctStreak,
        ?string $dudaUsuario = null,
        bool $applyAdaptation = true,
    ): array
    {
        $options = (array) ($question->options ?? []);
        $selected = $options[$selectedIndex] ?? '';
        $correct = (string) ($question->correct_answer ?? '');
        $isCorrect = $selected !== '' && $selected === $correct;

        if ($applyAdaptation) {
            $this->difficultyService->adjustDifficulty($user, $subject, $isCorrect);
        }

        $nivel = $isCorrect && $correctStreak >= 3 ? 'subir' : ($isCorrect ? 'mantener' : 'bajar');
        $context = $this->vector->technicalContextForQuestion($question);

        return $this->normalizeOutput(null, $isCorrect, $nivel, $question, $context, $dudaUsuario);
    }

    private function normalizeOutput(?array $ai, bool $isCorrect, string $nivel, Question $question, string $context, ?string $dudaUsuario): array
    {
        $default = [
            'evaluacion' => [
                'feedback_especifico' => $isCorrect
                    ? 'Resolución correcta. Mantén el mismo enfoque lógico para consolidar el tema.'
                    : 'La selección no coincide con la lógica del reactivo. Revisa el concepto base antes de responder.',
                'semblanza_tema' => $this->buildSemblanza($context),
            ],
            'chat' => [
                'respuesta_directa' => $this->defaultChatReply($dudaUsuario),
                'es_fuera_de_contexto' => false,
            ],
            'metadatos' => [
                'nivel_sugerido' => $nivel,
            ],
        ];

        if (! is_array($ai)) {
            return $default;
        }

        $out = array_replace_recursive($default, $ai);

        if (! isset($out['evaluacion']) || ! is_array($out['evaluacion'])) {
            $out['evaluacion'] = [];
        }

        if (isset($out['evaluacion']['semblanza_educativa']) && ! isset($out['evaluacion']['semblanza_tema'])) {
            $out['evaluacion']['semblanza_tema'] = $out['evaluacion']['semblanza_educativa'];
        }

        unset($out['evaluacion']['semblanza_educativa']);

        if (! isset($out['evaluacion']['feedback_especifico']) || ! is_string($out['evaluacion']['feedback_especifico'])) {
            $out['evaluacion']['feedback_especifico'] = $default['evaluacion']['feedback_especifico'];
        }

        if (! isset($out['evaluacion']['semblanza_tema']) || ! is_string($out['evaluacion']['semblanza_tema'])) {
            $out['evaluacion']['semblanza_tema'] = $default['evaluacion']['semblanza_tema'];
        }

        if (! in_array((string) ($out['metadatos']['nivel_sugerido'] ?? ''), ['subir', 'bajar', 'mantener'], true)) {
            $out['metadatos']['nivel_sugerido'] = $nivel;
        }

        $out['chat']['respuesta_directa'] = (string) ($out['chat']['respuesta_directa'] ?? $default['chat']['respuesta_directa']);
        $out['chat']['es_fuera_de_contexto'] = (bool) ($out['chat']['es_fuera_de_contexto'] ?? false);

        return $out;
    }

    private function buildSemblanza(string $context): string
    {
        $text = trim($context);
        if ($text === '') {
            return 'Este tema exige identificar el concepto rector, aplicar su definición formal y verificar consistencia lógica con las opciones.\n\nPara mejorar, compara cada distractor contra el principio teórico central y justifica por qué no satisface la condición del problema.';
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [];
        $first = implode(' ', array_slice($sentences, 0, 2));
        $second = implode(' ', array_slice($sentences, 2, 2));

        $first = trim($first) !== '' ? trim($first) : 'El contexto técnico del tema establece los principios fundamentales para resolver el reactivo.';
        $second = trim($second) !== '' ? trim($second) : 'La estrategia recomendada es aplicar esos principios paso a paso y contrastar cada distractor con la definición formal.';

        return $first . "\n\n" . $second;
    }

    private function defaultChatReply(?string $dudaUsuario): string
    {
        $duda = trim((string) $dudaUsuario);
        if ($duda === '') {
            return 'Si tienes una duda puntual del reactivo, escríbela y te guío sobre el tema actual.';
        }

        return 'Solo puedo asesorarte sobre el tema del examen actual.';
    }

    private function resolveWeakSubjects(User $user)
    {
        $availableSubjects = Subject::query()
            ->selectRaw('subjects.id, subjects.name, COUNT(questions.id) as questions_count')
            ->join('topics', 'topics.subject_id', '=', 'subjects.id')
            ->join('questions', 'questions.topic_id', '=', 'topics.id')
            ->where('questions.is_active', true)
            ->groupBy('subjects.id', 'subjects.name')
            ->get()
            ->keyBy('id');

        if ($availableSubjects->isEmpty()) {
            return collect();
        }

        $performanceBySubject = ExamAnswer::query()
            ->selectRaw('subjects.id as subject_id, SUM(CASE WHEN exam_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count, COUNT(*) as total_count')
            ->join('questions', 'questions.id', '=', 'exam_answers.question_id')
            ->join('topics', 'topics.id', '=', 'questions.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->join('exams', 'exams.id', '=', 'exam_answers.exam_id')
            ->where('exams.user_id', $user->id)
            ->groupBy('subjects.id')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_count;
                $correct = (int) $row->correct_count;

                return [
                    'subject_id' => (int) $row->subject_id,
                    'accuracy' => $total > 0 ? (int) round(($correct / $total) * 100) : null,
                ];
            })
            ->sortBy(fn (array $row) => $row['accuracy'] ?? 100)
            ->values();

        $selected = collect();

        foreach ($performanceBySubject as $row) {
            $subject = $availableSubjects->get($row['subject_id']);
            if (! $subject) {
                continue;
            }

            $selected->push([
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
                'accuracy' => $row['accuracy'],
            ]);

            if ($selected->count() >= 2) {
                break;
            }
        }

        if ($selected->count() < 2) {
            $needed = 2 - $selected->count();
            $extras = $availableSubjects
                ->reject(fn ($subject) => $selected->contains(fn (array $item) => (int) $item['id'] === (int) $subject->id))
                ->shuffle()
                ->take($needed)
                ->map(fn ($subject) => [
                    'id' => (int) $subject->id,
                    'name' => (string) $subject->name,
                    'accuracy' => null,
                ]);

            $selected = $selected->concat($extras)->values();
        }

        return $selected->take(2)->values();
    }
}
