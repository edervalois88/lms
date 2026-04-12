<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'slot',
        'rarity',
        'cost_xp',
        'is_active',
        'limited_from',
        'limited_until',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'cost_xp' => 'integer',
            'limited_from' => 'datetime',
            'limited_until' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function userRewardItems(): HasMany
    {
        return $this->hasMany(UserRewardItem::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(RewardPurchase::class);
    }
}
