<?php

namespace App\Services\Learning;

use App\Models\ExamAnswer;
use App\Models\User;
use App\Models\XpLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    public const XP_PRACTICE_SHORT = 15;
    public const XP_SUBJECT_RANK_UP = 100;
    public const XP_CONSISTENCY_BONUS = 200;
    public const XP_TUTOR_HINT_COST = 20;
    public const XP_SIMULATION_COMPLETE = 150;
    public const XP_BOOTCAMP_ADAPTIVE = 30;

    public function getCurrentXp(User $user): int
    {
        return (int) ($user->preferences['xp'] ?? 0);
    }

    /**
     * Backward-compatible alias used by legacy controllers.
     */
    public function addXP(User $user, int $amount): void
    {
        $this->earnXp($user, $amount, 'legacy_add_xp');
    }

    public function earnXp(User $user, int $amount, string $eventType, array $meta = []): array
    {
        $amount = max(0, $amount);

        if ($amount === 0) {
            return [
                'earned' => 0,
                'current_xp' => $this->getCurrentXp($user),
                'event_type' => $eventType,
            ];
        }

        return DB::transaction(function () use ($user, $amount, $eventType, $meta) {
            $user->refresh();
            $currentXP = $this->getCurrentXp($user);
            $newXP = $currentXP + $amount;

            $preferences = (array) ($user->preferences ?? []);
            $preferences['xp'] = $newXP;

            $user->forceFill(['preferences' => $preferences])->save();

            XpLedger::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'direction' => 'earn',
                'amount' => $amount,
                'balance_after' => $newXP,
                'meta' => $meta,
            ]);

            return [
                'earned' => $amount,
                'current_xp' => $newXP,
                'event_type' => $eventType,
            ];
        });
    }

    public function spendXp(User $user, int $amount, string $eventType, array $meta = []): array
    {
        $amount = max(0, $amount);

        return DB::transaction(function () use ($user, $amount, $eventType, $meta) {
            $user->refresh();
            $currentXP = $this->getCurrentXp($user);

            if ($currentXP < $amount) {
                return [
                    'ok' => false,
                    'spent' => 0,
                    'current_xp' => $currentXP,
                    'required_xp' => $amount,
                ];
            }

            $newXP = $currentXP - $amount;
            $preferences = (array) ($user->preferences ?? []);
            $preferences['xp'] = $newXP;

            $user->forceFill(['preferences' => $preferences])->save();

            XpLedger::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'direction' => 'spend',
                'amount' => $amount,
                'balance_after' => $newXP,
                'meta' => $meta,
            ]);

            return [
                'ok' => true,
                'spent' => $amount,
                'current_xp' => $newXP,
                'required_xp' => $amount,
            ];
        });
    }

    public function awardPracticeCompletion(User $user, int $subjectId): array
    {
        return $this->earnXp($user, self::XP_PRACTICE_SHORT, 'practice_completion', [
            'subject_id' => $subjectId,
        ]);
    }

    public function awardSimulationCompletion(User $user, int $examId): array
    {
        return $this->earnXp($user, self::XP_SIMULATION_COMPLETE, 'simulation_completion', [
            'exam_id' => $examId,
        ]);
    }

    public function awardAdaptiveBootcampCompletion(User $user, int $examId): array
    {
        return $this->earnXp($user, self::XP_BOOTCAMP_ADAPTIVE, 'adaptive_bootcamp_completion', [
            'exam_id' => $examId,
        ]);
    }

    public function awardDailyStreakXp(User $user, int $streakDays): array
    {
        $alreadyAwardedToday = XpLedger::query()
            ->where('user_id', $user->id)
            ->where('event_type', 'daily_streak')
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($alreadyAwardedToday) {
            return ['earned' => 0, 'current_xp' => $this->getCurrentXp($user), 'event_type' => 'daily_streak'];
        }

        $xp = min(50, max(10, $streakDays * 10));

        return $this->earnXp($user, $xp, 'daily_streak', [
            'streak_days' => $streakDays,
            'awarded_for_date' => Carbon::today()->toDateString(),
        ]);
    }

    public function awardConsistencyBonusIfEligible(User $user): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $practiceCount = XpLedger::query()
            ->where('user_id', $user->id)
            ->where('event_type', 'practice_completion')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();

        if ($practiceCount < 3) {
            return ['earned' => 0, 'current_xp' => $this->getCurrentXp($user), 'event_type' => 'consistency_bonus'];
        }

        $alreadyAwarded = XpLedger::query()
            ->where('user_id', $user->id)
            ->where('event_type', 'consistency_bonus')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->exists();

        if ($alreadyAwarded) {
            return ['earned' => 0, 'current_xp' => $this->getCurrentXp($user), 'event_type' => 'consistency_bonus'];
        }

        return $this->earnXp($user, self::XP_CONSISTENCY_BONUS, 'consistency_bonus', [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
        ]);
    }

    public function evaluateSubjectRank(User $user, int $subjectId): array
    {
        $recentAnswers = ExamAnswer::query()
            ->whereHas('exam', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('question.topic', fn ($query) => $query->where('subject_id', $subjectId))
            ->whereNotNull('is_correct')
            ->latest()
            ->take(50)
            ->get(['is_correct']);

        $total = $recentAnswers->count();
        $correct = $recentAnswers->where('is_correct', true)->count();
        $accuracy = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

        $rank = $this->rankForAccuracy($accuracy);
        $preferences = (array) ($user->preferences ?? []);
        $bestBySubject = (array) ($preferences['subject_rank_best'] ?? []);
        $subjectKey = (string) $subjectId;
        $previousBest = (int) ($bestBySubject[$subjectKey] ?? 0);
        $unlocked = [];

        if ($rank['index'] > $previousBest) {
            $bestBySubject[$subjectKey] = $rank['index'];
            $preferences['subject_rank_best'] = $bestBySubject;
            $user->forceFill(['preferences' => $preferences])->save();

            $this->earnXp($user, self::XP_SUBJECT_RANK_UP, 'subject_rank_up', [
                'subject_id' => $subjectId,
                'rank' => $rank['name'],
                'accuracy' => $accuracy,
            ]);

            $unlocked[] = $rank['name'];
        }

        return [
            'accuracy' => $accuracy,
            'rank' => $rank,
            'unlocked_badges' => $unlocked,
        ];
    }

    public function getLevel(User $user): array
    {
        $xp = $this->getCurrentXp($user);
        $level = floor(sqrt($xp / 100)) + 1;
        
        $nextLevelXP = pow($level, 2) * 100;
        $currentLevelXP = pow($level - 1, 2) * 100;
        
        $progress = $xp > 0 ? (($xp - $currentLevelXP) / ($nextLevelXP - $currentLevelXP)) * 100 : 0;

        return [
            'current' => $level,
            'xp' => $xp,
            'next_level_xp' => $nextLevelXP,
            'progress' => round($progress),
            'rank' => $this->getRankName($level)
        ];
    }

    private function getRankName(int $level): string
    {
        if ($level >= 50) return 'Aspirante Legendario';
        if ($level >= 30) return 'Maestro del Examen';
        if ($level >= 15) return 'Estudiante Avanzado';
        if ($level >= 5) return 'Aspirante Activo';
        return 'Novato';
    }

    private function rankForAccuracy(int $accuracy): array
    {
        return match (true) {
            $accuracy <= 20 => ['index' => 1, 'name' => 'Novato', 'icon' => 'seedling'],
            $accuracy <= 40 => ['index' => 2, 'name' => 'En progreso', 'icon' => 'leaf'],
            $accuracy <= 60 => ['index' => 3, 'name' => 'Competente', 'icon' => 'shield-bronze'],
            $accuracy <= 80 => ['index' => 4, 'name' => 'Fuerte', 'icon' => 'shield-silver'],
            default => ['index' => 5, 'name' => 'Dominio Absoluto', 'icon' => 'crown-gold'],
        };
    }
}
