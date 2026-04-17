import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAvatarStore = defineStore('avatar', () => {
  const equipped = ref({
    color: 'purple',
    outfit: 'student_robes',
    accessories: [],
    pet: 'dragon_purple',
    background: 'library',
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
   * Server format: { color: { code }, outfit: { code }, pet: { code }, background: { code } }
   * Null/undefined safe: skips missing or null slots.
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
  }

  return {
    equipped,
    setEquipped,
    hydrateFromEquipped,
  };
});
