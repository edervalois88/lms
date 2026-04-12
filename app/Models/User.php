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

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
}
