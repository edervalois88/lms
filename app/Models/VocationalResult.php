<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocationalResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'scores',
        'primary_type',
        'recommendation',
    ];

    protected $casts = [
        'scores' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
