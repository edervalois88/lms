<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTutorCache extends Model
{
    use HasFactory;

    protected $table = 'ai_tutor_cache';

    protected $fillable = [
        'question_id',
        'respuesta_incorrecta',
        'explicacion_ia',
        'hit_count',
    ];

    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
