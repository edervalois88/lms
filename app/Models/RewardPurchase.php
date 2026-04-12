<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_item_id',
        'xp_cost',
        'status',
        'idempotency_key',
        'purchased_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'xp_cost' => 'integer',
            'purchased_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(RewardItem::class);
    }
}
