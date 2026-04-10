<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTopicMastery extends Model
{
    use HasFactory;

    protected $table = 'user_topic_mastery';

    protected $fillable = [
        'user_id',
        'topic_id',
        'mastery_score',
        'total_attempts',
        'correct_attempts',
        'avg_time_seconds',
        'last_practiced_at',
    ];

    protected function casts(): array
    {
        return [
            'mastery_score' => 'decimal:4',
            'last_practiced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function getMasteryPercentageAttribute(): float
    {
        return (float) $this->mastery_score * 100;
    }
}
