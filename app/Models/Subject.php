<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'exam_areas',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'exam_areas' => 'array',
        ];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function scopeByArea(Builder $query, int $area): Builder
    {
        return $query->whereJsonContains('exam_areas', $area);
    }
}
