<?php

namespace App\Http\Controllers\Rewards;

use App\Http\Controllers\Controller;
use App\Models\RewardItem;
use App\Models\UserRewardItem;
use App\Services\Rewards\RewardInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RewardStoreController extends Controller
{
    public function __construct(protected RewardInventoryService $rewards) {}

    public function catalog(Request $request): JsonResponse
    {
        if (! $this->rewardTablesReady()) {
            return response()->json(['catalog' => []]);
        }

        return response()->json([
            'catalog' => $this->rewards->getCatalogForUser($request->user()),
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        if (! $this->rewardTablesReady()) {
            return response()->json(['inventory' => [], 'equipped' => []]);
        }

        $user = $request->user();

        return response()->json([
            'inventory' => $this->rewards->getInventoryForUser($user),
            'equipped' => $this->rewards->getEquippedForUser($user),
        ]);
    }

    public function purchase(Request $request): JsonResponse
    {
        if (! $this->rewardTablesReady()) {
            return response()->json(['message' => 'La tienda aun no esta disponible hasta ejecutar las migraciones pendientes.'], 503);
        }

        $data = $request->validate([
            'reward_item_id' => ['required', 'integer', 'exists:reward_items,id'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $rewardItem = RewardItem::query()->findOrFail($data['reward_item_id']);
        $result = $this->rewards->purchase($request->user(), $rewardItem, $data['idempotency_key'] ?? null);

        if (! ($result['ok'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function equip(Request $request): JsonResponse
    {
        if (! $this->rewardTablesReady()) {
            return response()->json(['message' => 'El inventario aun no esta disponible hasta ejecutar las migraciones pendientes.'], 503);
        }

        $data = $request->validate([
            'user_reward_item_id' => ['required', 'integer', 'exists:user_reward_items,id'],
        ]);

        $ownedItem = UserRewardItem::query()
            ->with('rewardItem')
            ->findOrFail($data['user_reward_item_id']);

        $result = $this->rewards->equip($request->user(), $ownedItem);

        if (! ($result['ok'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function shopPage(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Gamification/Shop');
    }

    public function avatarPage(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Gamification/Avatar');
    }

    public function state(Request $request): JsonResponse
    {
        if (! $this->rewardTablesReady()) {
            return response()->json(['message' => 'Reward system not ready'], 503);
        }

        $user = $request->user();

        return response()->json([
            'gold' => $user->gamification['gold'] ?? 0,
            'xp' => $user->gamification['xp'] ?? 0,
            'current_level' => $user->gamification['current_level'] ?? 1,
            'achievements_unlocked' => $user->gamification['achievements_unlocked'] ?? [],
            'inventory' => $this->rewards->getInventoryForUser($user),
            'equipped' => $this->rewards->getEquippedForUser($user),
        ]);
    }

    private function rewardTablesReady(): bool
    {
        return Schema::hasTable('reward_items')
            && Schema::hasTable('user_reward_items')
            && Schema::hasTable('user_reward_equips')
            && Schema::hasTable('reward_purchases');
    }
}
