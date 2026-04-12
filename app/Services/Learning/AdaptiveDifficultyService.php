<?php

namespace App\Services\Learning;

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamAnswer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AdaptiveDifficultyService
{
    private const DEFAULT_DIFFICULTY = 5;

    public function getCurrentDifficulty(User $user, Subject $subject): int
    {
        $key = "difficulty:{$user->id}:{$subject->id}";

        try {
            return (int) Redis::get($key) ?: self::DEFAULT_DIFFICULTY;
        } catch (\Throwable $exception) {
            Log::warning('Redis unavailable; using default adaptive difficulty.', [
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'error' => $exception->getMessage(),
            ]);

            return self::DEFAULT_DIFFICULTY;
        }
    }

    public function adjustDifficulty(User $user, Subject $subject, bool $wasCorrect): int
    {
        $lastAnswers = ExamAnswer::whereHas('exam', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('question.topic', function ($q) use ($subject) {
                $q->where('subject_id', $subject->id);
            })
            ->latest()
            ->take(5)
            ->get();

        $currentDifficulty = $this->getCurrentDifficulty($user, $subject);
        $correctStreak = 0;
        $incorrectStreak = 0;

        foreach ($lastAnswers as $answer) {
            if ($answer->is_correct) {
                $correctStreak++;
                $incorrectStreak = 0;
            } else {
                $incorrectStreak++;
                $correctStreak = 0;
            }
        }

        if ($correctStreak >= 3 && $currentDifficulty < 10) {
            $currentDifficulty++;
        } elseif ($incorrectStreak >= 2 && $currentDifficulty > 1) {
            $currentDifficulty--;
        }

        $key = "difficulty:{$user->id}:{$subject->id}";
        try {
            Redis::setex($key, 86400, $currentDifficulty);
        } catch (\Throwable $exception) {
            Log::warning('Redis unavailable; adaptive difficulty was not persisted.', [
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'difficulty' => $currentDifficulty,
                'error' => $exception->getMessage(),
            ]);
        }

        return $currentDifficulty;
    }
}
