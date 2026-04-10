<?php

namespace App\Services\Learning;

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamAnswer;
use Carbon\Carbon;

class ProgressCalculatorService
{
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
                'subject' => $subject->name,
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
        
        // Pesos reales UNAM Área 1 (Ingenierías)
        $weights = [
            'Matemáticas' => 26,
            'Física' => 24,
            'Química' => 10,
            'Biología' => 10,
            'Historia Universal' => 10,
            'Historia de México' => 10,
            'Español' => 14,
            'Literatura' => 8,
            'Geografía' => 8,
        ];

        $projectedScore = 0;
        foreach ($mastery as $m) {
            $weight = $weights[$m['subject']] ?? 0;
            $projectedScore += ($m['mastery_score'] / 10) * $weight;
        }

        return [
            'projected_score' => round($projectedScore),
            'min_score' => round($projectedScore * 0.9),
            'max_score' => min(120, round($projectedScore * 1.1)),
            'confidence' => 'Media'
        ];
    }

    public function getWeeklyStats(User $user): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        $answers = ExamAnswer::whereHas('exam', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('created_at', '>=', $startOfWeek)
            ->get();

        return [
            'questions_answered' => $answers->count(),
            'avg_accuracy' => $answers->count() > 0 ? round(($answers->where('is_correct', true)->count() / $answers->count()) * 100) : 0,
            'estimated_study_time' => round($answers->sum('time_spent_seconds') / 3600, 1),
            'most_practiced_subject' => 'Matemáticas' // Mock logic
        ];
    }

    private function calculateTrend(iterable $answers): string
    {
        return 'up'; // Simplified mock logic
    }
}
