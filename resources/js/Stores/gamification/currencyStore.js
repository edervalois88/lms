import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCurrencyStore = defineStore('currency', () => {
  // State
  const gold = ref(0);
  const xp = ref(0);
  const currentLevel = ref(1);

  // Computed
  const canAfford = computed(() => {
    return (cost) => gold.value >= cost;
  });

  // Actions
  const hydrate = (data) => {
    gold.value = data.gold || 0;
    xp.value = data.xp || 0;
    currentLevel.value = data.current_level || 1;
  };

  const addGold = (amount) => {
    gold.value += amount;
  };

  const addXP = (amount) => {
    xp.value += amount;
  };

  const spendGold = (amount) => {
    if (gold.value >= amount) {
      gold.value -= amount;
      return true;
    }
    return false;
  };

  return {
    gold,
    xp,
    currentLevel,
    canAfford,
    hydrate,
    addGold,
    addXP,
    spendGold,
  };
});
