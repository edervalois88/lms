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
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
