<?php

namespace App\Services;

use App\Enums\ExamType;
use App\Models\Exam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class FreemiumLimitService
{
    public const FEATURE_AI_TUTOR = 'ai_tutor';
    public const FEATURE_SIMULATION = 'simulation';

    private const FREE_AI_TUTOR_DAILY_LIMIT = 5;
    private const FREE_SIMULATION_WEEKLY_LIMIT = 1;

    public function assertCanUseAiTutor(User $user): void
    {
        if ($this->canUseAiTutor($user)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'error' => 'freemium_limit_reached',
            'feature' => self::FEATURE_AI_TUTOR,
        ], 403));
    }

    public function assertCanStartSimulation(User $user): void
    {
        if ($this->canStartSimulation($user)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'error' => 'freemium_limit_reached',
            'feature' => self::FEATURE_SIMULATION,
        ], 403));
    }

    public function canUseAiTutor(User $user): bool
    {
        if ($user->isPremium()) {
            return true;
        }

        $hitsToday = (int) DB::table('freemium_usages')
            ->where('user_id', $user->id)
            ->where('feature', self::FEATURE_AI_TUTOR)
            ->where('usage_date', Carbon::today()->toDateString())
            ->value('hits');

        return $hitsToday < self::FREE_AI_TUTOR_DAILY_LIMIT;
    }

    public function canStartSimulation(User $user): bool
    {
        if ($user->isPremium()) {
            return true;
        }

        $recentSimulations = Exam::query()
            ->where('user_id', $user->id)
            ->where('type', ExamType::Simulation)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        return $recentSimulations < self::FREE_SIMULATION_WEEKLY_LIMIT;
    }

    public function registerAiTutorUsage(User $user): void
    {
        if ($user->isPremium()) {
            return;
        }

        DB::transaction(function () use ($user) {
            $today = Carbon::today()->toDateString();

            $existing = DB::table('freemium_usages')
                ->where('user_id', $user->id)
                ->where('feature', self::FEATURE_AI_TUTOR)
                ->where('usage_date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                DB::table('freemium_usages')
                    ->where('id', $existing->id)
                    ->update([
                        'hits' => ((int) $existing->hits) + 1,
                        'updated_at' => now(),
                    ]);

                return;
            }

            DB::table('freemium_usages')->insert([
                'user_id' => $user->id,
                'feature' => self::FEATURE_AI_TUTOR,
                'usage_date' => $today,
                'hits' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
