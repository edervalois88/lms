<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MajorStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'major_id',
        'year',
        'cutoff_score',
        'applicants',
        'places_offered',
    ];

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }
}
