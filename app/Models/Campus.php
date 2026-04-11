<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_id',
        'name',
        'slug',
        'location',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }
}
