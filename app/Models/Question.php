<?php

namespace App\Models;

use App\Enums\ExamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'created_by',
        'type',
        'stem',
        'options',
        'correct_answer',
        'explanation',
        'difficulty',
        'is_ai_generated',
        'is_active',
    ];

    protected $appends = ['body', 'correct_index'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'type' => 'string',
        ];
    }

    /** Alias used by QuestionCard component */
    public function getBodyAttribute(): string
    {
        return $this->stem;
    }

    /** Numeric index of the correct option, used by QuestionCard for visual feedback */
    public function getCorrectIndexAttribute(): int
    {
        $idx = array_search($this->correct_answer, $this->options ?? []);
        return $idx !== false ? (int) $idx : 0;
    }

    /** Randomize options and return the correct answer index */
    public function randomizeOptions(): void
    {
        if (!is_array($this->options) || empty($this->options)) {
            return;
        }

        $options = $this->options;
        shuffle($options);
        $this->options = $options;
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class)->withTimestamps();
    }
}
