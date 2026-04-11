<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VocationalQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'ria_type',
        'order',
        'is_active',
    ];
}
