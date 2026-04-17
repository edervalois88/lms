<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    private const BASELINE_ANSWER_THRESHOLD = 25;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_premium',
        'preferences',
        'onboarded_at',
        'streak_days',
        'last_study_at',
        'major_id',
        'gpa',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_premium' => 'boolean',
            'preferences' => 'array',
            'onboarded_at' => 'datetime',
            'last_study_at' => 'datetime',
            'gpa' => 'float',
        ];
    }

    public function major(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function topicMasteries(): HasMany
    {
        return $this->hasMany(UserTopicMastery::class);
    }

    public function aiInteractions(): HasMany
    {
        return $this->hasMany(AiInteraction::class);
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function xpLedgers(): HasMany
    {
        return $this->hasMany(XpLedger::class);
    }

    public function rewardItems(): HasMany
    {
        return $this->hasMany(UserRewardItem::class);
    }

    public function rewardEquips(): HasMany
    {
        return $this->hasMany(UserRewardEquip::class);
    }

    public function rewardPurchases(): HasMany
    {
        return $this->hasMany(RewardPurchase::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    private ?array $gamificationStateCache = null;

    public function getGamificationStateAttribute(): array
    {
        if ($this->gamificationStateCache !== null) {
            return $this->gamificationStateCache;
        }

        $xpLedger = $this->xpLedgers()
            ->selectRaw('SUM(amount) as total_xp')
            ->first();

        $currentXp = max(0, (int) ($xpLedger->total_xp ?? 0));

        // Calculate level and progress based on progressCalculations.js logic
        // For now, simple: level = floor(xp / 1000) + 1
        $currentLevel = (int) floor($currentXp / 1000) + 1;

        $state = [
            'gold' => $currentXp,  // Removed redundant max(0, ...) — already clamped above
            'xp' => $currentXp,
            'current_level' => $currentLevel,
            'achievements_unlocked' => $this->achievements()
                ->pluck('achievement_id')
                ->all(),
        ];

        return $this->gamificationStateCache = $state;
    }

    public function isPremium(): bool
    {
        return (bool) $this->is_premium;
    }

    public function isFree(): bool
    {
        return ! $this->isPremium();
    }

    public function hasCompletedBaseline(): bool
    {
        $hasCompletedExam = $this->exams()->where('status', 'completed')->exists();
        if ($hasCompletedExam) {
            return true;
        }

        $answerCount = $this->exams()
            ->join('exam_answers', 'exam_answers.exam_id', '=', 'exams.id')
            ->where('exams.user_id', $this->id)
            ->count();

        return $answerCount >= self::BASELINE_ANSWER_THRESHOLD;
    }
}
