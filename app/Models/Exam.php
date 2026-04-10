<?php

namespace App\Models;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'exam_area',
        'total_questions',
        'time_limit_minutes',
        'status',
        'score',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamType::class,
            'status' => ExamStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ExamStatus::Completed);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', ExamStatus::InProgress);
    }
}
