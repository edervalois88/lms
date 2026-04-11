<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Major extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'name',
        'slug',
        'division_name',
        'min_score',
        'applicants',
        'places',
        'holland_code',
        'description',
        'extra_requirements',
    ];

    protected $casts = [
        'extra_requirements' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($major) {
            if (!$major->slug) {
                $major->slug = Str::slug($major->name);
            }
        });
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function statistics(): HasMany
    {
        return $table->hasMany(MajorStatistic::class);
    }

    /**
     * Calcula la métrica de dificultad (Places / Applicants) * 100
     */
    public function getDifficultyIndexAttribute(): float
    {
        if (!$this->applicants || $this->applicants === 0) return 0;
        return round(($this->places / $this->applicants) * 100, 2);
    }

    /**
     * Retorna la categoría de dificultad basada en el índice
     */
    public function getDifficultyCategoryAttribute(): string
    {
        $index = $this->difficulty_index;
        if ($index <= 5) return 'EXTREMA'; // Menos del 5% entra
        if ($index <= 15) return 'ALTA';
        if ($index <= 30) return 'MEDIA';
        return 'NORMAL';
    }
}
