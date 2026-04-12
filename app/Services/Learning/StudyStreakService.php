<?php

namespace App\Services\Learning;

use App\Models\User;
use Carbon\Carbon;

class StudyStreakService
{
    public function recordStudyActivity(User $user, ?Carbon $at = null): void
    {
        $now = $at ?? Carbon::now();
        $lastStudy = $user->last_study_at ? Carbon::parse($user->last_study_at) : null;

        if (!$lastStudy) {
            $user->forceFill([
                'streak_days' => 1,
                'last_study_at' => $now,
            ])->save();
            return;
        }

        if ($lastStudy->isSameDay($now)) {
            // Keep streak value and refresh timestamp for telemetry.
            $user->forceFill([
                'last_study_at' => $now,
            ])->save();
            return;
        }

        $newStreak = $lastStudy->isYesterday() ? ((int) $user->streak_days + 1) : 1;

        $user->forceFill([
            'streak_days' => $newStreak,
            'last_study_at' => $now,
        ])->save();
    }
}
