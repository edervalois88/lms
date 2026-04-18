<?php

namespace App\Services\Learning;

use App\Enums\AchievementId;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Evaluate and unlock achievements for a user based on activity.
     *
     * @param User $user
     * @param string $activityType e.g., 'quiz_complete', 'simulator_complete', 'streak_milestone'
     * @param array $context Activity data: score, questions_answered, subject, streak_days, etc.
     * @return array List of achievement IDs that were newly unlocked
     */
    public function evaluateAchievements(User $user, string $activityType, array $context): array
    {
        $newlyUnlocked = [];

        switch ($activityType) {
            case 'quiz_complete':
                $newlyUnlocked = $this->evaluateQuizAchievements($user, $context);
                break;
            case 'simulator_complete':
                $newlyUnlocked = $this->evaluateSimulatorAchievements($user, $context);
                break;
            case 'streak_milestone':
                $newlyUnlocked = $this->evaluateStreakAchievements($user, $context);
                break;
        }

        return $newlyUnlocked;
    }

    private function evaluateQuizAchievements(User $user, array $context): array
    {
        $newlyUnlocked = [];

        // first_quiz: Unlock on first quiz completion ever
        if (! $this->isAlreadyUnlocked($user, AchievementId::FIRST_QUIZ)) {
            $newlyUnlocked[] = AchievementId::FIRST_QUIZ;
            $this->unlock($user, AchievementId::FIRST_QUIZ, 'accessory_badge');
        }

        return $newlyUnlocked;
    }

    private function evaluateSimulatorAchievements(User $user, array $context): array
    {
        $newlyUnlocked = [];

        // simulator_perfect: Unlock if score is 100
        if (
            ! $this->isAlreadyUnlocked($user, AchievementId::SIMULATOR_PERFECT)
            && ($context['score'] ?? 0) === 100
        ) {
            $newlyUnlocked[] = AchievementId::SIMULATOR_PERFECT;
            $this->unlock($user, AchievementId::SIMULATOR_PERFECT, 'accessory_crown');
        }

        return $newlyUnlocked;
    }

    private function evaluateStreakAchievements(User $user, array $context): array
    {
        $newlyUnlocked = [];
        $streakDays = $context['streak_days'] ?? 0;

        // streak_7_days: Unlock at 7-day streak
        if (
            ! $this->isAlreadyUnlocked($user, AchievementId::STREAK_7_DAYS)
            && $streakDays >= 7
        ) {
            $newlyUnlocked[] = AchievementId::STREAK_7_DAYS;
            $this->unlock($user, AchievementId::STREAK_7_DAYS, 'accessory_blue_flame');
        }

        // streak_30_days: Unlock at 30-day streak
        if (
            ! $this->isAlreadyUnlocked($user, AchievementId::STREAK_30_DAYS)
            && $streakDays >= 30
        ) {
            $newlyUnlocked[] = AchievementId::STREAK_30_DAYS;
            $this->unlock($user, AchievementId::STREAK_30_DAYS, 'pet_golden_dragon');
        }

        return $newlyUnlocked;
    }

    private function isAlreadyUnlocked(User $user, string $achievementId): bool
    {
        return $user->achievements()
            ->where('achievement_id', $achievementId)
            ->exists();
    }

    private function unlock(User $user, string $achievementId, ?string $cosmeticUnlocked): void
    {
        DB::transaction(function () use ($user, $achievementId, $cosmeticUnlocked) {
            $user->achievements()->create([
                'achievement_id' => $achievementId,
                'cosmetic_unlocked' => $cosmeticUnlocked,
                'unlocked_at' => now(),
            ]);
        });
    }
}
