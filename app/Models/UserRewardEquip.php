<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRewardEquip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slot',
        'user_reward_item_id',
        'equipped_at',
    ];

    protected function casts(): array
    {
        return [
            'equipped_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userRewardItem(): BelongsTo
    {
        return $this->belongsTo(UserRewardItem::class);
    }
}
