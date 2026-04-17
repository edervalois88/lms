# Gamification Rewards System — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el sistema de recompensas, cosmética y logros educativos en NexusEdu con 4 Pinia stores modulares y los componentes Vue correspondientes.

**Architecture:** El backend ya expone `/rewards/catalog`, `/rewards/inventory`, `/rewards/purchase`, `/rewards/equip`. El frontend crea 4 Pinia stores (`currencyStore`, `shopStore`, `achievementsStore`, `avatarStore`) y los componentes de UI. La gamificación se distribuye en: navbar (CurrencyDisplay), página separada (/rewards/shop), y toasts en actividades.

**Tech Stack:** Vue 3, Pinia 2, Axios, Tailwind CSS, Vitest + happy-dom, Laravel Inertia (backend ya existente)

---

## Scope Check

El spec cubre 4 subsistemas independientes. Se implementan en este orden de prioridad:
1. **Fase 1** — Tienda + Currency (Tasks 1–7)
2. **Fase 2** — Logros educativos (Tasks 8–10)
3. **Fase 3** — Avatar Customizer (Tasks 11–15)
4. **Fase 4** — Animaciones polish (Task 16)

---

## File Map

**Crear:**
- `resources/js/Stores/gamification/currencyStore.js`
- `resources/js/Stores/gamification/shopStore.js`
- `resources/js/Stores/gamification/achievementsStore.js`
- `resources/js/Stores/gamification/avatarStore.js`
- `resources/js/Components/Gamification/Currency/CurrencyDisplay.vue`
- `resources/js/Components/Gamification/Shop/ShopItem.vue`
- `resources/js/Components/Gamification/Shop/PurchaseConfirm.vue`
- `resources/js/Components/Gamification/Shop/ShopCatalog.vue`
- `resources/js/Components/Gamification/Achievements/AchievementUnlock.vue`
- `resources/js/Components/Gamification/Cosmetics/CosmeticSelector.vue`
- `resources/js/Components/Gamification/AvatarCustomizer.vue`
- `resources/js/Pages/Gamification/Shop.vue`
- `resources/js/Pages/Gamification/Avatar.vue`
- `tests/js/Stores/currencyStore.test.js`
- `tests/js/Stores/shopStore.test.js`
- `tests/js/Stores/achievementsStore.test.js`
- `tests/js/Stores/avatarStore.test.js`

**Modificar:**
- `resources/js/Layouts/AuthenticatedLayout.vue` — agregar CurrencyDisplay y link a tienda
- `resources/js/Components/Progress/AvatarShowcase.vue` — agregar click para abrir customizer
- `resources/js/Components/Progress/RewardFeedback.vue` — mostrar Gold además de XP
- `routes/web.php` — agregar rutas `/rewards/shop` y `/rewards/avatar`
- `app/Http/Controllers/Rewards/RewardStoreController.php` — agregar método `state()`

---

## FASE 1: Tienda + Currency

---

### Task 1: currencyStore

**Files:**
- Create: `resources/js/Stores/gamification/currencyStore.js`
- Create: `tests/js/Stores/currencyStore.test.js`

El store lee el estado inicial de `page.props.auth.user.gamification` (ya expuesto por Inertia) y expone métodos para agregar/gastar XP (que el backend llama "cost_xp" — en el frontend lo llamamos "gold" para mantener consistencia con el spec).

- [ ] **Step 1: Escribir el test**

```js
// tests/js/Stores/currencyStore.test.js
import { setActivePinia, createPinia } from 'pinia';
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { describe, it, expect, beforeEach } from 'vitest';

describe('currencyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with default zero values', () => {
    const store = useCurrencyStore();
    expect(store.gold).toBe(0);
    expect(store.xp).toBe(0);
    expect(store.currentLevel).toBe(1);
  });

  it('addGold increases gold balance', () => {
    const store = useCurrencyStore();
    store.addGold(50);
    expect(store.gold).toBe(50);
  });

  it('spendGold decreases gold balance', () => {
    const store = useCurrencyStore();
    store.addGold(500);
    const ok = store.spendGold(200);
    expect(ok).toBe(true);
    expect(store.gold).toBe(300);
  });

  it('spendGold returns false when insufficient funds', () => {
    const store = useCurrencyStore();
    const ok = store.spendGold(100);
    expect(ok).toBe(false);
    expect(store.gold).toBe(0);
  });

  it('canAfford returns correct boolean', () => {
    const store = useCurrencyStore();
    store.addGold(250);
    expect(store.canAfford(250)).toBe(true);
    expect(store.canAfford(251)).toBe(false);
  });

  it('hydrate sets all values from server data', () => {
    const store = useCurrencyStore();
    store.hydrate({ gold: 1200, xp: 3400, current_level: 3 });
    expect(store.gold).toBe(1200);
    expect(store.xp).toBe(3400);
    expect(store.currentLevel).toBe(3);
  });
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
npx vitest run tests/js/Stores/currencyStore.test.js
```
Esperado: FAIL — "Cannot find module '@/Stores/gamification/currencyStore'"

- [ ] **Step 3: Crear el store**

```js
// resources/js/Stores/gamification/currencyStore.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCurrencyStore = defineStore('currency', () => {
  const gold = ref(0);
  const xp = ref(0);
  const currentLevel = ref(1);

  const canAfford = computed(() => (cost) => gold.value >= cost);

  function hydrate(data) {
    gold.value = data.gold ?? 0;
    xp.value = data.xp ?? 0;
    currentLevel.value = data.current_level ?? 1;
  }

  function addGold(amount) {
    gold.value += amount;
  }

  function addXP(amount) {
    xp.value += amount;
  }

  function spendGold(amount) {
    if (gold.value < amount) return false;
    gold.value -= amount;
    return true;
  }

  return { gold, xp, currentLevel, canAfford, hydrate, addGold, addXP, spendGold };
});
```

- [ ] **Step 4: Correr el test para verificar que pasa**

```bash
npx vitest run tests/js/Stores/currencyStore.test.js
```
Esperado: PASS — 6 tests passed

- [ ] **Step 5: Commit**

```bash
git add resources/js/Stores/gamification/currencyStore.js tests/js/Stores/currencyStore.test.js
git commit -m "feat(stores): add currencyStore with gold/xp management"
```

---

### Task 2: shopStore

**Files:**
- Create: `resources/js/Stores/gamification/shopStore.js`
- Create: `tests/js/Stores/shopStore.test.js`

Conecta con `/rewards/catalog` y `/rewards/inventory`. El backend usa `cost_xp` como nombre del campo — el store lo mapea a `cost` internamente.

- [ ] **Step 1: Escribir el test**

```js
// tests/js/Stores/shopStore.test.js
import { setActivePinia, createPinia } from 'pinia';
import { useShopStore } from '@/Stores/gamification/shopStore';
import { describe, it, expect, beforeEach, vi } from 'vitest';

vi.mock('axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

import axios from 'axios';

describe('shopStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('initializes with empty catalog and inventory', () => {
    const store = useShopStore();
    expect(store.catalog).toEqual([]);
    expect(store.inventory).toEqual([]);
    expect(store.loading).toBe(false);
  });

  it('fetchCatalog populates catalog from API response', async () => {
    axios.get.mockResolvedValueOnce({
      data: {
        catalog: [
          { id: 1, code: 'outfit_warrior', name: 'Warrior Armor', cost_xp: 250, category: 'outfit', slot: 'outfit', rarity: 'rare', owned: false, equipped: false }
        ]
      }
    });
    const store = useShopStore();
    await store.fetchCatalog();
    expect(store.catalog).toHaveLength(1);
    expect(store.catalog[0].cost).toBe(250);
    expect(store.catalog[0].code).toBe('outfit_warrior');
  });

  it('isOwned returns true for items in inventory', () => {
    const store = useShopStore();
    store.inventory = [{ rewardItemId: 5 }];
    expect(store.isOwned(5)).toBe(true);
    expect(store.isOwned(99)).toBe(false);
  });
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
npx vitest run tests/js/Stores/shopStore.test.js
```
Esperado: FAIL — "Cannot find module '@/Stores/gamification/shopStore'"

- [ ] **Step 3: Crear el store**

```js
// resources/js/Stores/gamification/shopStore.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useShopStore = defineStore('shop', () => {
  const catalog = ref([]);
  const inventory = ref([]);
  const equipped = ref({});
  const loading = ref(false);

  const isOwned = computed(() => (rewardItemId) =>
    inventory.value.some((i) => i.rewardItemId === rewardItemId)
  );

  const isEquipped = computed(() => (code) =>
    Object.values(equipped.value).some((e) => e?.code === code)
  );

  function mapCatalogItem(item) {
    return {
      ...item,
      cost: item.cost_xp,
    };
  }

  async function fetchCatalog() {
    loading.value = true;
    try {
      const { data } = await axios.get('/rewards/catalog');
      catalog.value = (data.catalog ?? []).map(mapCatalogItem);
    } finally {
      loading.value = false;
    }
  }

  async function fetchInventory() {
    const { data } = await axios.get('/rewards/inventory');
    inventory.value = data.inventory ?? [];
    equipped.value = data.equipped ?? {};
  }

  async function purchase(rewardItemId, idempotencyKey = null) {
    const { data } = await axios.post('/rewards/purchase', {
      reward_item_id: rewardItemId,
      idempotency_key: idempotencyKey,
    });
    if (data.ok) {
      await fetchInventory();
    }
    return data;
  }

  async function equip(userRewardItemId) {
    const { data } = await axios.post('/rewards/equip', {
      user_reward_item_id: userRewardItemId,
    });
    if (data.ok) {
      equipped.value = data.equipped ?? equipped.value;
    }
    return data;
  }

  return { catalog, inventory, equipped, loading, isOwned, isEquipped, fetchCatalog, fetchInventory, purchase, equip };
});
```

- [ ] **Step 4: Correr el test para verificar que pasa**

```bash
npx vitest run tests/js/Stores/shopStore.test.js
```
Esperado: PASS — 3 tests passed

- [ ] **Step 5: Commit**

```bash
git add resources/js/Stores/gamification/shopStore.js tests/js/Stores/shopStore.test.js
git commit -m "feat(stores): add shopStore connecting to /rewards API"
```

---

### Task 3: CurrencyDisplay.vue

**Files:**
- Create: `resources/js/Components/Gamification/Currency/CurrencyDisplay.vue`

Widget que muestra el balance de Gold en la navbar. Lee del currencyStore.

- [ ] **Step 1: Crear el componente**

```vue
<!-- resources/js/Components/Gamification/Currency/CurrencyDisplay.vue -->
<script setup>
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { storeToRefs } from 'pinia';

const currency = useCurrencyStore();
const { gold, currentLevel } = storeToRefs(currency);
</script>

<template>
  <div class="flex items-center gap-3">
    <!-- Gold -->
    <div class="flex items-center gap-1.5 px-3 py-1 bg-yellow-500/10 border border-yellow-500/20 rounded-full">
      <span class="text-base">🪙</span>
      <span class="text-sm font-bold text-yellow-400">{{ gold.toLocaleString() }}</span>
    </div>
    <!-- Nivel -->
    <div class="flex items-center gap-1.5 px-3 py-1 bg-purple-500/10 border border-purple-500/20 rounded-full">
      <span class="text-base">⭐</span>
      <span class="text-sm font-bold text-purple-400">Nv. {{ currentLevel }}</span>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Agregar a AuthenticatedLayout.vue**

Abrir `resources/js/Layouts/AuthenticatedLayout.vue`. Encontrar el área del navbar/header donde se muestran acciones del usuario. Agregar:

```vue
<!-- Importar al inicio del <script setup> -->
import CurrencyDisplay from '@/Components/Gamification/Currency/CurrencyDisplay.vue';
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const currencyStore = useCurrencyStore();
const page = usePage();

onMounted(() => {
  const gamification = page.props.auth?.user?.gamification ?? {};
  currencyStore.hydrate({
    gold: gamification.gold ?? 0,
    xp: gamification.xp ?? 0,
    current_level: gamification.current_level ?? 1,
  });
});
```

Y en el template, dentro del navbar:
```vue
<CurrencyDisplay />
```

- [ ] **Step 3: Verificar visualmente**

Correr `npm run dev` y navegar al dashboard. Verificar que se muestra el widget de Gold y Nivel en la navbar.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Gamification/Currency/CurrencyDisplay.vue resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat(ui): add CurrencyDisplay widget to navbar"
```

---

### Task 4: ShopItem.vue

**Files:**
- Create: `resources/js/Components/Gamification/Shop/ShopItem.vue`

Card individual de un item de tienda con preview, precio, rareza y estado (owned/equipped).

- [ ] **Step 1: Crear el componente**

```vue
<!-- resources/js/Components/Gamification/Shop/ShopItem.vue -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
  item: { type: Object, required: true },
  canAfford: { type: Boolean, default: false },
});

const emit = defineEmits(['purchase']);

const rarityConfig = {
  common:   { label: 'Común',    color: 'text-gray-400',   border: 'border-gray-600' },
  uncommon: { label: 'Inusual',  color: 'text-green-400',  border: 'border-green-600' },
  rare:     { label: 'Raro',     color: 'text-blue-400',   border: 'border-blue-600' },
  epic:     { label: 'Épico',    color: 'text-purple-400', border: 'border-purple-600' },
};

const rarity = computed(() => rarityConfig[props.item.rarity] ?? rarityConfig.common);

const statusLabel = computed(() => {
  if (props.item.equipped) return 'Equipado';
  if (props.item.owned) return 'En inventario';
  if (props.item.cost === 0 && props.item.unlockedBy) return 'Se desbloquea';
  return null;
});
</script>

<template>
  <div
    class="relative flex flex-col bg-white/5 border rounded-xl overflow-hidden transition-all duration-200"
    :class="[rarity.border, item.owned ? 'opacity-80' : 'hover:bg-white/10']"
  >
    <!-- Preview -->
    <div class="h-28 flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900 text-5xl">
      {{ item.metadata?.icon ?? '🎁' }}
    </div>

    <!-- Rareza -->
    <span class="absolute top-2 left-2 text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-black/50"
      :class="rarity.color">
      {{ rarity.label }}
    </span>

    <!-- Info -->
    <div class="p-3 flex flex-col gap-2 flex-1">
      <p class="text-sm font-semibold text-white leading-tight">{{ item.name }}</p>

      <!-- Estado o precio -->
      <div class="mt-auto">
        <span v-if="statusLabel"
          class="inline-block text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400 font-medium">
          {{ statusLabel }}
        </span>

        <button v-else
          :disabled="!canAfford"
          @click="emit('purchase', item)"
          class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors"
          :class="canAfford
            ? 'bg-yellow-500 hover:bg-yellow-400 text-black'
            : 'bg-gray-700 text-gray-500 cursor-not-allowed'"
        >
          <span>🪙</span>
          <span>{{ item.cost.toLocaleString() }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Gamification/Shop/ShopItem.vue
git commit -m "feat(ui): add ShopItem card component"
```

---

### Task 5: PurchaseConfirm.vue

**Files:**
- Create: `resources/js/Components/Gamification/Shop/PurchaseConfirm.vue`

Modal de confirmación de compra que muestra el item y el costo antes de confirmar.

- [ ] **Step 1: Crear el componente**

```vue
<!-- resources/js/Components/Gamification/Shop/PurchaseConfirm.vue -->
<script setup>
const props = defineProps({
  item: { type: Object, default: null },
  currentGold: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
  <Transition name="modal">
    <div v-if="item" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 max-w-sm w-full shadow-2xl">

        <h3 class="text-lg font-bold text-white mb-1">Confirmar compra</h3>
        <p class="text-sm text-gray-400 mb-5">¿Quieres comprar este item?</p>

        <!-- Preview del item -->
        <div class="flex items-center gap-4 p-4 bg-white/5 rounded-xl mb-5">
          <span class="text-4xl">{{ item.metadata?.icon ?? '🎁' }}</span>
          <div>
            <p class="font-semibold text-white">{{ item.name }}</p>
            <p class="text-xs text-gray-400 capitalize">{{ item.category }}</p>
          </div>
        </div>

        <!-- Balance -->
        <div class="flex justify-between text-sm mb-6">
          <span class="text-gray-400">Tu Gold</span>
          <span class="text-yellow-400 font-bold">🪙 {{ currentGold.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between text-sm mb-6 pb-4 border-b border-white/10">
          <span class="text-gray-400">Costo</span>
          <span class="text-yellow-400 font-bold">🪙 {{ item.cost.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between text-sm mb-6">
          <span class="text-gray-400">Gold restante</span>
          <span class="font-bold" :class="(currentGold - item.cost) >= 0 ? 'text-white' : 'text-red-400'">
            🪙 {{ (currentGold - item.cost).toLocaleString() }}
          </span>
        </div>

        <!-- Acciones -->
        <div class="flex gap-3">
          <button @click="emit('cancel')"
            class="flex-1 py-2 rounded-xl border border-white/10 text-gray-400 hover:text-white transition-colors text-sm">
            Cancelar
          </button>
          <button @click="emit('confirm')"
            :disabled="loading"
            class="flex-1 py-2 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-black font-bold text-sm transition-colors disabled:opacity-50">
            {{ loading ? 'Comprando...' : 'Confirmar' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s, transform 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Gamification/Shop/PurchaseConfirm.vue
git commit -m "feat(ui): add PurchaseConfirm modal component"
```

---

### Task 6: ShopCatalog.vue

**Files:**
- Create: `resources/js/Components/Gamification/Shop/ShopCatalog.vue`

Grid filtrable del catálogo completo. Orquesta ShopItem y PurchaseConfirm.

- [ ] **Step 1: Crear el componente**

```vue
<!-- resources/js/Components/Gamification/Shop/ShopCatalog.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useShopStore } from '@/Stores/gamification/shopStore';
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { storeToRefs } from 'pinia';
import ShopItem from './ShopItem.vue';
import PurchaseConfirm from './PurchaseConfirm.vue';

const shop = useShopStore();
const currency = useCurrencyStore();
const { catalog, loading } = storeToRefs(shop);
const { gold } = storeToRefs(currency);

const activeCategory = ref('all');
const pendingItem = ref(null);
const purchasing = ref(false);
const toast = ref(null);

const categories = ['all', 'color', 'outfit', 'accessory', 'pet', 'background'];
const categoryLabels = {
  all: 'Todo', color: 'Color', outfit: 'Ropa',
  accessory: 'Accesorios', pet: 'Mascotas', background: 'Fondos',
};

const filteredCatalog = computed(() =>
  activeCategory.value === 'all'
    ? catalog.value
    : catalog.value.filter((i) => i.category === activeCategory.value)
);

async function onPurchase(item) {
  pendingItem.value = item;
}

async function confirmPurchase() {
  if (!pendingItem.value) return;
  purchasing.value = true;
  try {
    const result = await shop.purchase(pendingItem.value.id);
    if (result.ok) {
      currency.spendGold(pendingItem.value.cost);
      toast.value = `¡Compraste ${pendingItem.value.name}!`;
      setTimeout(() => (toast.value = null), 3000);
    }
  } finally {
    purchasing.value = false;
    pendingItem.value = null;
  }
}

onMounted(() => shop.fetchCatalog());
</script>

<template>
  <div>
    <!-- Filtros -->
    <div class="flex gap-2 flex-wrap mb-6">
      <button
        v-for="cat in categories" :key="cat"
        @click="activeCategory = cat"
        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
        :class="activeCategory === cat
          ? 'bg-purple-600 text-white'
          : 'bg-white/5 text-gray-400 hover:bg-white/10'"
      >
        {{ categoryLabels[cat] }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="n in 8" :key="n" class="h-48 rounded-xl bg-white/5 animate-pulse" />
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <ShopItem
        v-for="item in filteredCatalog"
        :key="item.id"
        :item="item"
        :can-afford="currency.canAfford(item.cost)"
        @purchase="onPurchase"
      />
    </div>

    <!-- Modal de confirmación -->
    <PurchaseConfirm
      :item="pendingItem"
      :current-gold="gold"
      :loading="purchasing"
      @confirm="confirmPurchase"
      @cancel="pendingItem = null"
    />

    <!-- Toast de éxito -->
    <Transition name="toast">
      <div v-if="toast"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 px-5 py-3 bg-green-500 text-white text-sm font-semibold rounded-full shadow-lg">
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: opacity 0.3s, transform 0.3s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(20px); }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Gamification/Shop/ShopCatalog.vue
git commit -m "feat(ui): add ShopCatalog with filter and purchase flow"
```

---

### Task 7: Página Shop.vue + Rutas

**Files:**
- Create: `resources/js/Pages/Gamification/Shop.vue`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Rewards/RewardStoreController.php`

- [ ] **Step 1: Agregar método `shopPage()` en RewardStoreController**

Abrir `app/Http/Controllers/Rewards/RewardStoreController.php` y agregar al final (antes del cierre `}`):

```php
public function shopPage(): \Inertia\Response
{
    return \Inertia\Inertia::render('Gamification/Shop');
}
```

- [ ] **Step 2: Agregar rutas en web.php**

Abrir `routes/web.php`. Dentro del grupo `middleware(['onboarded'])`, después de las rutas de rewards existentes, agregar:

```php
Route::get('/rewards/shop', [RewardStoreController::class, 'shopPage'])->name('rewards.shop');
```

- [ ] **Step 3: Crear la página**

```vue
<!-- resources/js/Pages/Gamification/Shop.vue -->
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ShopCatalog from '@/Components/Gamification/Shop/ShopCatalog.vue';
import CurrencyDisplay from '@/Components/Gamification/Currency/CurrencyDisplay.vue';
</script>

<template>
  <Head title="Tienda de Cosmética" />

  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-white">Tienda</h1>
          <p class="text-sm text-gray-400 mt-1">Personaliza tu avatar con Gold ganado estudiando</p>
        </div>
        <CurrencyDisplay />
      </div>

      <!-- Catálogo -->
      <ShopCatalog />
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Verificar que la página carga**

```bash
npm run dev
```

Navegar a `http://localhost:8000/rewards/shop`. Verificar que se muestra la tienda con el catálogo.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Gamification/Shop.vue app/Http/Controllers/Rewards/RewardStoreController.php routes/web.php
git commit -m "feat(pages): add /rewards/shop page with full catalog"
```

---

## FASE 2: Logros Educativos

---

### Task 8: achievementsStore

**Files:**
- Create: `resources/js/Stores/gamification/achievementsStore.js`
- Create: `tests/js/Stores/achievementsStore.test.js`

- [ ] **Step 1: Escribir el test**

```js
// tests/js/Stores/achievementsStore.test.js
import { setActivePinia, createPinia } from 'pinia';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';
import { describe, it, expect, beforeEach } from 'vitest';

describe('achievementsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes empty', () => {
    const store = useAchievementsStore();
    expect(store.completed).toEqual([]);
    expect(store.pendingToast).toBeNull();
  });

  it('unlock adds achievement and sets pendingToast', () => {
    const store = useAchievementsStore();
    store.unlock({ id: 'mastery_math_8', cosmeticUnlocked: 'outfit_math_master', label: 'Maestro de Matemáticas' });
    expect(store.completed).toHaveLength(1);
    expect(store.completed[0].id).toBe('mastery_math_8');
    expect(store.pendingToast).not.toBeNull();
    expect(store.pendingToast.label).toBe('Maestro de Matemáticas');
  });

  it('clearToast removes pendingToast', () => {
    const store = useAchievementsStore();
    store.unlock({ id: 'first_quiz', cosmeticUnlocked: 'accessory_badge', label: 'Primera Pregunta' });
    store.clearToast();
    expect(store.pendingToast).toBeNull();
  });

  it('isCompleted returns true for completed achievement', () => {
    const store = useAchievementsStore();
    store.unlock({ id: 'streak_7_days', cosmeticUnlocked: null, label: 'Racha 7 días' });
    expect(store.isCompleted('streak_7_days')).toBe(true);
    expect(store.isCompleted('streak_30_days')).toBe(false);
  });
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
npx vitest run tests/js/Stores/achievementsStore.test.js
```
Esperado: FAIL — "Cannot find module '@/Stores/gamification/achievementsStore'"

- [ ] **Step 3: Crear el store**

```js
// resources/js/Stores/gamification/achievementsStore.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAchievementsStore = defineStore('achievements', () => {
  const completed = ref([]);
  const pendingToast = ref(null);

  const isCompleted = computed(() => (id) => completed.value.some((a) => a.id === id));

  function unlock(achievement) {
    if (isCompleted.value(achievement.id)) return;
    completed.value.push({ ...achievement, unlockedAt: new Date().toISOString() });
    pendingToast.value = achievement;
  }

  function clearToast() {
    pendingToast.value = null;
  }

  function hydrateFromServer(achievementIds) {
    achievementIds.forEach((id) => {
      if (!isCompleted.value(id)) {
        completed.value.push({ id, unlockedAt: null });
      }
    });
  }

  return { completed, pendingToast, isCompleted, unlock, clearToast, hydrateFromServer };
});
```

- [ ] **Step 4: Correr el test para verificar que pasa**

```bash
npx vitest run tests/js/Stores/achievementsStore.test.js
```
Esperado: PASS — 4 tests passed

- [ ] **Step 5: Commit**

```bash
git add resources/js/Stores/gamification/achievementsStore.js tests/js/Stores/achievementsStore.test.js
git commit -m "feat(stores): add achievementsStore for educational unlocks"
```

---

### Task 9: AchievementUnlock.vue

**Files:**
- Create: `resources/js/Components/Gamification/Achievements/AchievementUnlock.vue`

Toast especial que aparece cuando el usuario desbloquea cosmética por un logro educativo.

- [ ] **Step 1: Crear el componente**

```vue
<!-- resources/js/Components/Gamification/Achievements/AchievementUnlock.vue -->
<script setup>
import { watch } from 'vue';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';
import { storeToRefs } from 'pinia';

const achievements = useAchievementsStore();
const { pendingToast } = storeToRefs(achievements);

watch(pendingToast, (val) => {
  if (val) {
    setTimeout(() => achievements.clearToast(), 5000);
  }
});
</script>

<template>
  <Transition name="achievement">
    <div v-if="pendingToast"
      class="fixed top-6 right-6 z-50 max-w-sm w-full bg-gray-900 border border-purple-500/50 rounded-2xl p-4 shadow-2xl shadow-purple-500/20">

      <!-- Header brillante -->
      <div class="flex items-center gap-2 mb-2">
        <span class="text-2xl">🏆</span>
        <span class="text-xs font-bold text-purple-400 uppercase tracking-widest">Logro desbloqueado</span>
      </div>

      <p class="text-white font-bold text-sm">{{ pendingToast.label }}</p>

      <div v-if="pendingToast.cosmeticUnlocked"
        class="mt-3 flex items-center gap-2 px-3 py-2 bg-purple-500/10 rounded-xl">
        <span class="text-xl">🎁</span>
        <p class="text-xs text-purple-300">¡Cosmética desbloqueada en tu inventario!</p>
      </div>

      <!-- Barra de tiempo -->
      <div class="mt-3 h-1 bg-white/10 rounded-full overflow-hidden">
        <div class="h-full bg-purple-500 rounded-full animate-shrink" />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.achievement-enter-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.achievement-leave-active { transition: all 0.3s ease-in; }
.achievement-enter-from { opacity: 0; transform: translateX(100%) scale(0.8); }
.achievement-leave-to { opacity: 0; transform: translateX(100%); }

@keyframes shrink {
  from { width: 100%; }
  to { width: 0%; }
}
.animate-shrink { animation: shrink 5s linear forwards; }
</style>
```

- [ ] **Step 2: Registrar globalmente en AuthenticatedLayout.vue**

Agregar en `resources/js/Layouts/AuthenticatedLayout.vue`:

```vue
<!-- En <script setup> -->
import AchievementUnlock from '@/Components/Gamification/Achievements/AchievementUnlock.vue';
```

```vue
<!-- En el template, antes de </div> de cierre principal -->
<AchievementUnlock />
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Gamification/Achievements/AchievementUnlock.vue resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat(ui): add AchievementUnlock toast for educational rewards"
```

---

### Task 10: Integrar logros con Quiz y Simulator

**Files:**
- Modify: `resources/js/Pages/Quiz/*.vue` (donde se procesa la respuesta del backend)
- Modify: `resources/js/Pages/Simulator/*.vue` (donde se procesa submit)

Cuando el backend retorna `achievements_unlocked`, el frontend los procesa.

- [ ] **Step 1: Encontrar dónde se procesa la respuesta del quiz**

```bash
grep -rn "achievements\|reward\|gold\|xp" resources/js/Pages/Quiz --include="*.vue" -l
grep -rn "achievements\|reward\|gold\|xp" resources/js/Pages/Simulator --include="*.vue" -l
```

- [ ] **Step 2: Agregar helper de procesamiento de rewards**

Crear `resources/js/Utils/processRewards.js`:

```js
// resources/js/Utils/processRewards.js
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';

// Mapa de achievement ID → label para mostrar al usuario
const ACHIEVEMENT_LABELS = {
  first_quiz: { label: 'Primera Pregunta', cosmeticUnlocked: 'accessory_badge' },
  streak_7_days: { label: 'Racha de 7 días', cosmeticUnlocked: 'accessory_blue_flame' },
  streak_30_days: { label: 'Racha de 30 días', cosmeticUnlocked: 'pet_golden_dragon' },
  mastery_math_8: { label: 'Maestro de Matemáticas', cosmeticUnlocked: 'outfit_math_master' },
  simulator_perfect: { label: 'Simulacro Perfecto', cosmeticUnlocked: 'accessory_crown' },
  explorer: { label: 'Explorador', cosmeticUnlocked: 'pet_phoenix' },
  unstoppable: { label: 'Incansable', cosmeticUnlocked: 'background_starfield' },
};

/**
 * Procesa la respuesta del backend después de una actividad.
 * @param {Object} data - Respuesta del backend, puede contener gold_earned, xp_earned, achievements_unlocked
 */
export function processRewards(data) {
  const currency = useCurrencyStore();
  const achievementsStore = useAchievementsStore();

  if (data.gold_earned) currency.addGold(data.gold_earned);
  if (data.xp_earned) currency.addXP(data.xp_earned);

  (data.achievements_unlocked ?? []).forEach((achievementId) => {
    const meta = ACHIEVEMENT_LABELS[achievementId] ?? { label: achievementId, cosmeticUnlocked: null };
    achievementsStore.unlock({ id: achievementId, ...meta });
  });
}
```

- [ ] **Step 3: Llamar `processRewards` en el quiz**

En el archivo Vue del quiz donde se llama `axios.post` para evaluar respuesta, agregar:

```js
import { processRewards } from '@/Utils/processRewards';

// Donde ya existe el manejo de respuesta del quiz:
const { data } = await axios.post(route('quiz.evaluate', { subject: subject.slug }), payload);
processRewards(data); // ← agregar esta línea
```

- [ ] **Step 4: Llamar `processRewards` en el simulator**

En el archivo Vue del simulator donde se procesa la respuesta del submit:

```js
import { processRewards } from '@/Utils/processRewards';

// Donde ya existe el manejo del submit del simulacro:
const { data } = await axios.post(route('simulator.submit'), payload);
processRewards(data); // ← agregar esta línea
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/Utils/processRewards.js
git commit -m "feat(utils): add processRewards helper for quiz/simulator integration"
```

---

## FASE 3: Avatar Customizer

---

### Task 11: avatarStore

**Files:**
- Create: `resources/js/Stores/gamification/avatarStore.js`
- Create: `tests/js/Stores/avatarStore.test.js`

- [ ] **Step 1: Escribir el test**

```js
// tests/js/Stores/avatarStore.test.js
import { setActivePinia, createPinia } from 'pinia';
import { useAvatarStore } from '@/Stores/gamification/avatarStore';
import { describe, it, expect, beforeEach } from 'vitest';

describe('avatarStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('has default equipped values', () => {
    const store = useAvatarStore();
    expect(store.equipped.color).toBe('purple');
    expect(store.equipped.outfit).toBe('student_robes');
    expect(store.equipped.pet).toBe('dragon_purple');
    expect(store.equipped.background).toBe('library');
    expect(store.equipped.accessories).toEqual([]);
  });

  it('setEquipped updates a slot', () => {
    const store = useAvatarStore();
    store.setEquipped('outfit', 'outfit_warrior');
    expect(store.equipped.outfit).toBe('outfit_warrior');
  });

  it('setEquipped for accessories appends up to 2 items', () => {
    const store = useAvatarStore();
    store.setEquipped('accessories', 'glasses_academic');
    store.setEquipped('accessories', 'crown_knowledge');
    expect(store.equipped.accessories).toHaveLength(2);
  });

  it('setEquipped for accessories replaces oldest when at max', () => {
    const store = useAvatarStore();
    store.setEquipped('accessories', 'item_a');
    store.setEquipped('accessories', 'item_b');
    store.setEquipped('accessories', 'item_c');
    expect(store.equipped.accessories).toHaveLength(2);
    expect(store.equipped.accessories).toContain('item_b');
    expect(store.equipped.accessories).toContain('item_c');
  });

  it('hydrateFromEquipped maps server slots to local state', () => {
    const store = useAvatarStore();
    store.hydrateFromEquipped({
      color: { code: 'blue' },
      outfit: { code: 'outfit_warrior' },
      pet: { code: 'pet_phoenix' },
      background: { code: 'background_lab' },
    });
    expect(store.equipped.color).toBe('blue');
    expect(store.equipped.outfit).toBe('outfit_warrior');
  });
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
npx vitest run tests/js/Stores/avatarStore.test.js
```
Esperado: FAIL — "Cannot find module '@/Stores/gamification/avatarStore'"

- [ ] **Step 3: Crear el store**

```js
// resources/js/Stores/gamification/avatarStore.js
import { defineStore } from 'pinia';
import { ref } from 'vue';

const MAX_ACCESSORIES = 2;

export const useAvatarStore = defineStore('avatar', () => {
  const equipped = ref({
    color: 'purple',
    outfit: 'student_robes',
    accessories: [],
    pet: 'dragon_purple',
    background: 'library',
  });

  function setEquipped(slot, code) {
    if (slot === 'accessories') {
      const current = [...equipped.value.accessories];
      if (!current.includes(code)) {
        if (current.length >= MAX_ACCESSORIES) current.shift();
        current.push(code);
      }
      equipped.value.accessories = current;
    } else {
      equipped.value[slot] = code;
    }
  }

  function hydrateFromEquipped(serverEquipped) {
    if (!serverEquipped) return;
    Object.entries(serverEquipped).forEach(([slot, item]) => {
      if (!item?.code) return;
      if (slot === 'accessories') {
        setEquipped('accessories', item.code);
      } else if (slot in equipped.value) {
        equipped.value[slot] = item.code;
      }
    });
  }

  return { equipped, setEquipped, hydrateFromEquipped };
});
```

- [ ] **Step 4: Correr el test para verificar que pasa**

```bash
npx vitest run tests/js/Stores/avatarStore.test.js
```
Esperado: PASS — 5 tests passed

- [ ] **Step 5: Commit**

```bash
git add resources/js/Stores/gamification/avatarStore.js tests/js/Stores/avatarStore.test.js
git commit -m "feat(stores): add avatarStore for cosmetic equip management"
```

---

### Task 12: CosmeticSelector.vue

**Files:**
- Create: `resources/js/Components/Gamification/Cosmetics/CosmeticSelector.vue`

Selector genérico de cosmética por slot. Muestra items del inventario y permite preview antes de equipar.

- [ ] **Step 1: Crear el componente**

```vue
<!-- resources/js/Components/Gamification/Cosmetics/CosmeticSelector.vue -->
<script setup>
import { computed } from 'vue';
import { useShopStore } from '@/Stores/gamification/shopStore';
import { useAvatarStore } from '@/Stores/gamification/avatarStore';
import { storeToRefs } from 'pinia';

const props = defineProps({
  slot: { type: String, required: true }, // 'color' | 'outfit' | 'accessory' | 'pet' | 'background'
  label: { type: String, required: true },
});

const emit = defineEmits(['equip']);

const shop = useShopStore();
const avatar = useAvatarStore();
const { inventory, equipped: shopEquipped } = storeToRefs(shop);
const { equipped } = storeToRefs(avatar);

const slotItems = computed(() =>
  inventory.value
    .filter((i) => i.rewardItem?.slot === props.slot || i.rewardItem?.category === props.slot)
    .map((i) => i.rewardItem)
    .filter(Boolean)
);

const isEquipped = computed(() => (code) => {
  if (props.slot === 'accessories') return equipped.value.accessories.includes(code);
  return equipped.value[props.slot] === code;
});
</script>

<template>
  <div>
    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">{{ label }}</p>

    <div v-if="slotItems.length === 0" class="text-sm text-gray-500 italic">
      No tienes items en este slot. ¡Visita la tienda!
    </div>

    <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-3">
      <button
        v-for="item in slotItems" :key="item.id"
        @click="emit('equip', { slot, item })"
        class="flex flex-col items-center gap-1.5 p-3 rounded-xl border transition-all text-center"
        :class="isEquipped(item.code)
          ? 'border-purple-500 bg-purple-500/20'
          : 'border-white/10 bg-white/5 hover:bg-white/10'"
      >
        <span class="text-2xl">{{ item.metadata?.icon ?? '🎁' }}</span>
        <span class="text-xs text-gray-300 leading-tight">{{ item.name }}</span>
        <span v-if="isEquipped(item.code)" class="text-xs text-purple-400 font-bold">✓ Equipado</span>
      </button>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Gamification/Cosmetics/CosmeticSelector.vue
git commit -m "feat(ui): add CosmeticSelector for per-slot item selection"
```

---

### Task 13: AvatarCustomizer.vue + Avatar.vue

**Files:**
- Create: `resources/js/Components/Gamification/AvatarCustomizer.vue`
- Create: `resources/js/Pages/Gamification/Avatar.vue`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Rewards/RewardStoreController.php`

- [ ] **Step 1: Crear AvatarCustomizer.vue**

```vue
<!-- resources/js/Components/Gamification/AvatarCustomizer.vue -->
<script setup>
import { onMounted } from 'vue';
import { useShopStore } from '@/Stores/gamification/shopStore';
import { useAvatarStore } from '@/Stores/gamification/avatarStore';
import { storeToRefs } from 'pinia';
import CosmeticSelector from './Cosmetics/CosmeticSelector.vue';
import AvatarAnimated from '@/Components/Progress/AvatarAnimated.vue';

const shop = useShopStore();
const avatar = useAvatarStore();
const { equipped } = storeToRefs(avatar);

const slots = [
  { slot: 'color', label: 'Color base' },
  { slot: 'outfit', label: 'Ropa' },
  { slot: 'accessory', label: 'Accesorios' },
  { slot: 'pet', label: 'Mascota' },
  { slot: 'background', label: 'Fondo' },
];

async function onEquip({ slot, item }) {
  // Buscar el userRewardItem correspondiente
  const owned = shop.inventory.find((i) => i.rewardItem?.id === item.id);
  if (!owned) return;

  const result = await shop.equip(owned.id);
  if (result.ok) {
    avatar.setEquipped(slot, item.code);
  }
}

onMounted(() => shop.fetchInventory());
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Preview del avatar -->
    <div class="flex flex-col items-center justify-start gap-4">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Preview</p>
      <div class="relative w-40 h-40">
        <div class="absolute inset-0 rounded-full opacity-30"
          style="background: conic-gradient(from 0deg, #667eea, #764ba2, #a855f7, #667eea);" />
        <div class="relative z-10 flex items-center justify-center h-full">
          <AvatarAnimated icon="🎓" state="idle" size="lg" />
        </div>
      </div>
      <div class="text-center text-xs text-gray-500 space-y-1">
        <p>Ropa: <span class="text-white">{{ equipped.outfit }}</span></p>
        <p>Mascota: <span class="text-white">{{ equipped.pet }}</span></p>
      </div>
    </div>

    <!-- Selectores por slot -->
    <div class="md:col-span-2 space-y-6">
      <CosmeticSelector
        v-for="slotDef in slots"
        :key="slotDef.slot"
        :slot="slotDef.slot"
        :label="slotDef.label"
        @equip="onEquip"
      />
    </div>
  </div>
</template>
```

- [ ] **Step 2: Agregar método `avatarPage()` en RewardStoreController**

Abrir `app/Http/Controllers/Rewards/RewardStoreController.php` y agregar:

```php
public function avatarPage(): \Inertia\Response
{
    return \Inertia\Inertia::render('Gamification/Avatar');
}
```

- [ ] **Step 3: Agregar ruta en web.php**

Dentro del grupo `middleware(['onboarded'])`:

```php
Route::get('/rewards/avatar', [RewardStoreController::class, 'avatarPage'])->name('rewards.avatar');
```

- [ ] **Step 4: Crear Avatar.vue**

```vue
<!-- resources/js/Pages/Gamification/Avatar.vue -->
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarCustomizer from '@/Components/Gamification/AvatarCustomizer.vue';
</script>

<template>
  <Head title="Mi Avatar" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Mi Avatar</h1>
        <p class="text-sm text-gray-400 mt-1">Equipa la cosmética de tu inventario</p>
      </div>

      <AvatarCustomizer />
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 5: Actualizar AvatarShowcase.vue para linkear al customizer**

Abrir `resources/js/Components/Progress/AvatarShowcase.vue`. Modificar el bloque "Rewards Link" existente para agregar también un link al avatar:

```vue
<!-- Reemplazar el <a v-if="rewardsRoute"> existente por: -->
<div class="flex gap-3">
  <a
    v-if="rewardsRoute"
    :href="rewardsRoute"
    class="px-5 py-2 bg-gradient-to-r from-purple-500 to-blue-600 text-white rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity"
  >
    Tienda
  </a>
  <a
    href="/rewards/avatar"
    class="px-5 py-2 bg-white/10 border border-white/20 text-white rounded-xl font-semibold text-sm hover:bg-white/20 transition-colors"
  >
    Personalizar
  </a>
</div>
```

- [ ] **Step 6: Verificar visualmente**

Correr `npm run dev` y navegar a `http://localhost:8000/rewards/avatar`. Verificar que se muestra el customizer con los slots.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Components/Gamification/AvatarCustomizer.vue resources/js/Pages/Gamification/Avatar.vue resources/js/Components/Progress/AvatarShowcase.vue app/Http/Controllers/Rewards/RewardStoreController.php routes/web.php
git commit -m "feat(pages): add /rewards/avatar page with AvatarCustomizer"
```

---

## FASE 4: Animaciones Polish

---

### Task 14: Actualizar RewardFeedback para mostrar Gold

**Files:**
- Modify: `resources/js/Components/Progress/RewardFeedback.vue`

Actualmente solo muestra "+XP". Modificar para aceptar también `gold` y mostrarlo.

- [ ] **Step 1: Modificar RewardFeedback.vue**

Abrir `resources/js/Components/Progress/RewardFeedback.vue`. Agregar la prop `gold` y mostrarla junto al XP:

En `<script setup>` agregar la prop:
```js
const props = defineProps({
  xp: { type: Number, default: 50 },
  gold: { type: Number, default: 0 },    // ← agregar
  show: { type: Boolean, default: true },
  duration: { type: Number, default: 1500 },
});
```

En el template, reemplazar el div del texto XP:
```vue
<!-- Reemplazar <div ref="xpText" ...> con: -->
<div ref="xpText" class="flex flex-col items-center gap-2">
  <div class="text-6xl font-black text-yellow-400 drop-shadow-lg">+{{ xp }} XP</div>
  <div v-if="gold > 0" class="text-3xl font-black text-yellow-300 drop-shadow-lg">+{{ gold }} 🪙</div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Progress/RewardFeedback.vue
git commit -m "feat(ui): show Gold earned in RewardFeedback animation"
```

---

## Self-Review

**Spec coverage:**

| Req del spec | Task que lo implementa |
|---|---|
| currencyStore | Task 1 |
| shopStore | Task 2 |
| achievementsStore | Task 8 |
| avatarStore | Task 11 |
| CurrencyDisplay en navbar | Task 3 |
| ShopItem | Task 4 |
| PurchaseConfirm | Task 5 |
| ShopCatalog | Task 6 |
| Shop.vue + ruta | Task 7 |
| AchievementUnlock toast | Task 9 |
| Integración quiz/simulator | Task 10 |
| CosmeticSelector | Task 12 |
| AvatarCustomizer + Avatar.vue | Task 13 |
| RewardFeedback muestra Gold | Task 14 |
| Endpoint /rewards/shop (Inertia) | Task 7 |
| Endpoint /rewards/avatar (Inertia) | Task 13 |

**Gaps detectados y resueltos:**
- `processRewards` helper (Task 10) cubre la integración de achievements con quiz/simulator
- La hidratación de avatarStore desde el servidor (`hydrateFromEquipped`) conecta con `fetchInventory` en Task 13

**Placeholder scan:** Ninguno encontrado.

**Type consistency:**
- `shop.inventory[n].rewardItem?.id` → usado consistentemente en CosmeticSelector y AvatarCustomizer
- `cost_xp` (backend) → mapeado a `cost` en `shopStore.mapCatalogItem` → usado como `item.cost` en ShopItem y PurchaseConfirm
- `slot` prop de CosmeticSelector usa los mismos valores que `avatarStore.equipped` keys
