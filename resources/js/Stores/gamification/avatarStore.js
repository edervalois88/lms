import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getDefaultCosmetic } from '@/Data/cosmetics.js';

export const useAvatarStore = defineStore('avatar', () => {
  // Initialize with default cosmetics
  const equipped = ref({
    color: getDefaultCosmetic('color'),
    outfit: getDefaultCosmetic('outfit'),
    accessories: [],
    pet: getDefaultCosmetic('pet'),
    background: getDefaultCosmetic('background'),
  });

  /**
   * Set a cosmetic slot value.
   * For 'accessories': appends up to max 2; if already 2, shifts oldest and pushes new.
   * For other slots: sets directly.
   */
  function setEquipped(slot, code) {
    if (slot === 'accessories') {
      if (equipped.value.accessories.length >= 2) {
        equipped.value.accessories.shift();
      }
      equipped.value.accessories.push(code);
    } else {
      equipped.value[slot] = code;
    }
  }

  /**
   * Hydrate equipped from server response.
   * Server format: { color: { code }, outfit: { code }, pet: { code }, background: { code }, accessories: [{ code }] }
   * Null/undefined safe: skips missing or null slots, uses defaults.
   */
  function hydrateFromEquipped(serverEquipped) {
    if (!serverEquipped) return;

    const simpleSlots = ['color', 'outfit', 'pet', 'background'];
    for (const slot of simpleSlots) {
      const entry = serverEquipped[slot];
      if (entry && entry.code != null) {
        equipped.value[slot] = entry.code;
      }
    }

    // Handle accessories array
    if (serverEquipped.accessories && Array.isArray(serverEquipped.accessories)) {
      equipped.value.accessories = serverEquipped.accessories
        .map((item) => item?.code)
        .filter((code) => code != null)
        .slice(0, 2); // Max 2 accessories
    }
  }

  return {
    equipped,
    setEquipped,
    hydrateFromEquipped,
  };
});
