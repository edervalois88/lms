<?php

namespace App\Services\Learning;

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamAnswer;
use Carbon\Carbon;

class ProgressCalculatorService
{
    public function __construct(
        protected ExamAreaResolver $areaResolver,
    ) {}

    public function getSubjectMastery(User $user): array
    {
        $subjects = Subject::all();
        $mastery = [];

        foreach ($subjects as $subject) {
            $lastAnswers = ExamAnswer::whereHas('exam', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->whereHas('question.topic', function ($q) use ($subject) {
                    $q->where('subject_id', $subject->id);
                })
                ->latest()
                ->take(20)
                ->get();

            $correct = $lastAnswers->where('is_correct', true)->count();
            $total = $lastAnswers->count();
            $accuracy = $total > 0 ? ($correct / $total) : 0;

            $mastery[] = [
                'id' => $subject->id,
                'subject' => $subject->name,
                'subject_color' => $subject->color,
                'mastery_score' => round($accuracy * 10, 1),
                'total_attempts' => $total,
                'correct_attempts' => $correct,
                'trend' => $this->calculateTrend($lastAnswers)
            ];
        }

        return $mastery;
    }

    public function getScoreProjection(User $user): array
    {
        $mastery = $this->getSubjectMastery($user);
        
        // Resuelve área desde datos actuales del major sin depender de columnas inexistentes.
        $area = $this->areaResolver->fromUser($user);
        $weights = $this->getWeightsForArea($area);

        $projectedScore = 0;
        foreach ($mastery as $m) {
            $weight = $weights[$m['subject']] ?? 0;
            $projectedScore += ($m['mastery_score'] / 10) * $weight;
        }

        $totalAttempts = array_sum(array_map(fn ($m) => (int) ($m['total_attempts'] ?? 0), $mastery));
        $confidence = match (true) {
            $totalAttempts >= 120 => 'Alta',
            $totalAttempts >= 40 => 'Media',
            default => 'Baja',
        };

        return [
            'projected_score' => round($projectedScore),
            'min_score' => round($projectedScore * 0.9),
            'max_score' => min(120, round($projectedScore * 1.1)),
            'confidence' => $confidence,
            'gap_to_goal' => $user->major ? ($user->major->min_score - round($projectedScore)) : null,
            'goal_name' => $user->major?->name ?? 'No definida'
        ];
    }

    private function getWeightsForArea(int $area): array
    {
        // Distribución real de aciertos por área en UNAM
        return match($area) {
            1 => [ // Físico-Matemáticas
                'Matemáticas' => 26, 'Física' => 24, 'Español' => 18, 'Literatura' => 10, 
                'Química' => 10, 'Biología' => 10, 'Historia Universal' => 10, 'Historia de México' => 10, 'Geografía' => 2
            ],
            2 => [ // Biológicas y Salud
                'Biología' => 22, 'Química' => 22, 'Matemáticas' => 22, 'Física' => 18, 
                'Español' => 18, 'Literatura' => 10, 'Historia Universal' => 10, 'Historia de México' => 10, 'Geografía' => 8
            ],
            default => [ // Genérico simplificado
                'Matemáticas' => 20, 'Español' => 20, 'Física' => 10, 'Química' => 10, 'Biología' => 10, 'Literatura' => 10, 'Historia Universal' => 10, 'Historia de México' => 10, 'Geografía' => 10
            ]
        };
    }

    public function getWeeklyStats(User $user): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        $answers = ExamAnswer::whereHas('exam', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('created_at', '>=', $startOfWeek)
            ->get();

        $mostPracticedSubject = 'Sin datos';
        $subjectCounts = ExamAnswer::query()
            ->whereHas('exam', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('created_at', '>=', $startOfWeek)
            ->whereHas('question.topic.subject')
            ->with('question.topic.subject:id,name')
            ->get()
            ->groupBy(fn ($answer) => $answer->question?->topic?->subject?->name)
            ->map->count()
            ->filter(fn ($count, $subject) => !empty($subject));

        if ($subjectCounts->isNotEmpty()) {
            $mostPracticedSubject = (string) $subjectCounts->sortDesc()->keys()->first();
        }

        return [
            'questions_answered' => $answers->count(),
            'avg_accuracy' => $answers->count() > 0 ? round(($answers->where('is_correct', true)->count() / $answers->count()) * 100) : 0,
            'estimated_study_time' => round($answers->sum('time_spent_seconds') / 3600, 1),
            'most_practiced_subject' => $mostPracticedSubject,
        ];
    }

    private function calculateTrend(iterable $answers): string
    {
        $answersArray = is_array($answers) ? $answers : iterator_to_array($answers);
        $count = count($answersArray);

        if ($count < 6) {
            return 'stable';
        }

        $half = intdiv($count, 2);
        $firstHalf = array_slice($answersArray, 0, $half);
        $secondHalf = array_slice($answersArray, $half);

        $firstAcc = $this->accuracyForSegment($firstHalf);
        $secondAcc = $this->accuracyForSegment($secondHalf);
        $delta = $secondAcc - $firstAcc;

        if ($delta >= 0.08) {
            return 'up';
        }

        if ($delta <= -0.08) {
            return 'down';
        }

        return 'stable';
    }

    private function accuracyForSegment(array $segment): float
    {
        if (count($segment) === 0) {
            return 0.0;
        }

        $correct = 0;

        foreach ($segment as $answer) {
            if ((bool) ($answer->is_correct ?? false)) {
                $correct++;
            }
        }

        return $correct / count($segment);
    }
}
