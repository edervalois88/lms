<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserRewardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_item_id',
        'obtained_via',
        'price_paid_xp',
        'purchased_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price_paid_xp' => 'integer',
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

    public function equipRecords(): HasMany
    {
        return $this->hasMany(UserRewardEquip::class);
    }
}
