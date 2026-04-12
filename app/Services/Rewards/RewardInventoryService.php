<?php

namespace App\Services\Rewards;

use App\Models\RewardItem;
use App\Models\RewardPurchase;
use App\Models\User;
use App\Models\UserRewardEquip;
use App\Models\UserRewardItem;
use App\Services\Learning\GamificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RewardInventoryService
{
    public function __construct(protected GamificationService $gamification) {}

    public function getCatalogForUser(User $user): array
    {
        if (! $this->hasRequiredTables()) {
            return [];
        }

        $ownedIds = UserRewardItem::query()
            ->where('user_id', $user->id)
            ->pluck('reward_item_id')
            ->all();

        $equipped = $this->getEquippedForUser($user);
        $equippedCodes = collect($equipped)->filter()->pluck('code')->all();

        return RewardItem::query()
            ->where('is_active', true)
            ->orderBy('slot')
            ->orderBy('cost_xp')
            ->get()
            ->filter(fn (RewardItem $item) => $this->isItemCurrentlyAvailable($item))
            ->map(fn (RewardItem $item) => $this->serializeRewardItem(
                $item,
                in_array($item->id, $ownedIds, true),
                in_array($item->code, $equippedCodes, true)
            ))
            ->values()
            ->all();
    }

    public function getInventoryForUser(User $user): array
    {
        if (! $this->hasRequiredTables()) {
            return [];
        }

        return UserRewardItem::query()
            ->with('rewardItem')
            ->where('user_id', $user->id)
            ->latest('purchased_at')
            ->get()
            ->map(fn (UserRewardItem $item) => $this->serializeOwnedItem($item))
            ->values()
            ->all();
    }

    public function getEquippedForUser(User $user): array
    {
        if (! $this->hasRequiredTables()) {
            return [];
        }

        $equips = UserRewardEquip::query()
            ->with('userRewardItem.rewardItem')
            ->where('user_id', $user->id)
            ->get();

        $slots = [];
        foreach ($equips as $equip) {
            $rewardItem = $equip->userRewardItem?->rewardItem;
            if (! $rewardItem) {
                continue;
            }

            $slots[$equip->slot] = $this->serializeRewardItem($rewardItem, true, true);
        }

        return $slots;
    }

    public function purchase(User $user, RewardItem $rewardItem, ?string $idempotencyKey = null): array
    {
        if (! $this->hasRequiredTables()) {
            return ['ok' => false, 'message' => 'La tienda aun no esta disponible hasta ejecutar las migraciones pendientes.'];
        }

        if (! $rewardItem->is_active || ! $this->isItemCurrentlyAvailable($rewardItem)) {
            return ['ok' => false, 'message' => 'Esta recompensa no esta disponible actualmente.'];
        }

        $alreadyOwned = UserRewardItem::query()
            ->where('user_id', $user->id)
            ->where('reward_item_id', $rewardItem->id)
            ->exists();

        if ($alreadyOwned) {
            return ['ok' => false, 'message' => 'Ya tienes esta recompensa en tu inventario.'];
        }

        $purchaseKey = $idempotencyKey ?: (string) Str::uuid();

        $existingPurchase = RewardPurchase::query()
            ->where('idempotency_key', $purchaseKey)
            ->where('user_id', $user->id)
            ->first();

        if ($existingPurchase && $existingPurchase->status === 'completed') {
            return [
                'ok' => true,
                'message' => 'Compra ya procesada.',
                'current_xp' => $this->gamification->getCurrentXp($user),
                'inventory' => $this->getInventoryForUser($user),
                'equipped' => $this->getEquippedForUser($user),
            ];
        }

        return DB::transaction(function () use ($user, $rewardItem, $purchaseKey) {
            $xpSpend = $this->gamification->spendXp($user, (int) $rewardItem->cost_xp, 'reward_purchase', [
                'reward_item_id' => $rewardItem->id,
                'reward_code' => $rewardItem->code,
                'idempotency_key' => $purchaseKey,
            ]);

            if (! ($xpSpend['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => 'XP insuficiente para desbloquear esta recompensa.',
                    'current_xp' => (int) ($xpSpend['current_xp'] ?? 0),
                ];
            }

            $purchase = RewardPurchase::create([
                'user_id' => $user->id,
                'reward_item_id' => $rewardItem->id,
                'xp_cost' => (int) $rewardItem->cost_xp,
                'status' => 'completed',
                'idempotency_key' => $purchaseKey,
                'purchased_at' => Carbon::now(),
                'meta' => [
                    'reward_code' => $rewardItem->code,
                    'slot' => $rewardItem->slot,
                ],
            ]);

            $ownedItem = UserRewardItem::create([
                'user_id' => $user->id,
                'reward_item_id' => $rewardItem->id,
                'obtained_via' => 'purchase',
                'price_paid_xp' => (int) $rewardItem->cost_xp,
                'purchased_at' => Carbon::now(),
                'meta' => [
                    'purchase_id' => $purchase->id,
                ],
            ]);

            if (! UserRewardEquip::query()->where('user_id', $user->id)->where('slot', $rewardItem->slot)->exists()) {
                UserRewardEquip::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'slot' => $rewardItem->slot,
                    ],
                    [
                        'user_reward_item_id' => $ownedItem->id,
                        'equipped_at' => Carbon::now(),
                    ]
                );
            }

            return [
                'ok' => true,
                'message' => 'Recompensa desbloqueada correctamente.',
                'purchase_id' => $purchase->id,
                'current_xp' => (int) ($xpSpend['current_xp'] ?? 0),
                'inventory' => $this->getInventoryForUser($user),
                'equipped' => $this->getEquippedForUser($user),
            ];
        });
    }

    public function equip(User $user, UserRewardItem $ownedItem): array
    {
        if (! $this->hasRequiredTables()) {
            return ['ok' => false, 'message' => 'El inventario aun no esta disponible hasta ejecutar las migraciones pendientes.'];
        }

        $ownedItem->loadMissing('rewardItem');

        if ((int) $ownedItem->user_id !== (int) $user->id) {
            return ['ok' => false, 'message' => 'No puedes equipar una recompensa que no te pertenece.'];
        }

        $slot = (string) $ownedItem->rewardItem?->slot;
        if ($slot === '') {
            return ['ok' => false, 'message' => 'La recompensa seleccionada no tiene un slot valido.'];
        }

        UserRewardEquip::updateOrCreate(
            [
                'user_id' => $user->id,
                'slot' => $slot,
            ],
            [
                'user_reward_item_id' => $ownedItem->id,
                'equipped_at' => Carbon::now(),
            ]
        );

        return [
            'ok' => true,
            'message' => 'Recompensa equipada correctamente.',
            'equipped' => $this->getEquippedForUser($user),
            'inventory' => $this->getInventoryForUser($user),
            'current_xp' => $this->gamification->getCurrentXp($user),
        ];
    }

    public function getUiCosmetics(User $user): array
    {
        return [
            'equipped' => $this->getEquippedForUser($user),
        ];
    }

    private function hasRequiredTables(): bool
    {
        return Schema::hasTable('reward_items')
            && Schema::hasTable('user_reward_items')
            && Schema::hasTable('user_reward_equips')
            && Schema::hasTable('reward_purchases');
    }

    private function serializeRewardItem(RewardItem $item, bool $owned = false, bool $equipped = false): array
    {
        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'category' => $item->category,
            'slot' => $item->slot,
            'rarity' => $item->rarity,
            'cost_xp' => (int) $item->cost_xp,
            'is_active' => (bool) $item->is_active,
            'metadata' => $item->metadata ?? [],
            'owned' => $owned,
            'equipped' => $equipped,
        ];
    }

    private function serializeOwnedItem(UserRewardItem $item): array
    {
        $reward = $item->rewardItem;

        return [
            'id' => $item->id,
            'obtained_via' => $item->obtained_via,
            'price_paid_xp' => (int) $item->price_paid_xp,
            'purchased_at' => optional($item->purchased_at)->toIso8601String(),
            'meta' => $item->meta ?? [],
            'reward_item' => $reward ? $this->serializeRewardItem($reward, true, false) : null,
        ];
    }

    private function isItemCurrentlyAvailable(RewardItem $item): bool
    {
        $now = Carbon::now();

        if ($item->limited_from && $now->lt($item->limited_from)) {
            return false;
        }

        if ($item->limited_until && $now->gt($item->limited_until)) {
            return false;
        }

        return true;
    }
}