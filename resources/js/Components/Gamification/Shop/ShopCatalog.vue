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
const categoryLabels = { all: 'Todo', color: 'Color', outfit: 'Ropa', accessory: 'Accesorios', pet: 'Mascotas', background: 'Fondos' };

const filteredCatalog = computed(() =>
  activeCategory.value === 'all' ? catalog.value : catalog.value.filter(i => i.category === activeCategory.value)
);

async function onPurchase(item) { pendingItem.value = item; }

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
    <div class="flex gap-2 flex-wrap mb-6">
      <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
        :class="activeCategory === cat ? 'bg-purple-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10'">
        {{ categoryLabels[cat] }}
      </button>
    </div>
    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="n in 8" :key="n" class="h-48 rounded-xl bg-white/5 animate-pulse" />
    </div>
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <ShopItem v-for="item in filteredCatalog" :key="item.id" :item="item"
        :can-afford="currency.canAfford(item.cost)" @purchase="onPurchase" />
    </div>
    <PurchaseConfirm :item="pendingItem" :current-gold="gold" :loading="purchasing"
      @confirm="confirmPurchase" @cancel="pendingItem = null" />
    <Transition name="toast">
      <div v-if="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 px-5 py-3 bg-green-500 text-white text-sm font-semibold rounded-full shadow-lg">
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: opacity 0.3s, transform 0.3s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(20px); }
</style>
