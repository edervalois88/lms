<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpacedRepetitionCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'ease_factor',
        'interval',
        'repetitions',
        'next_review_at',
    ];

    protected $casts = [
        'next_review_at' => 'datetime',
        'ease_factor' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
