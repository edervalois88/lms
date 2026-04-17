import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAvatarStore } from '@/Stores/gamification/avatarStore';

describe('avatarStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with correct default values', () => {
    const store = useAvatarStore();
    expect(store.equipped).toEqual({
      color: 'purple',
      outfit: 'student_robes',
      accessories: [],
      pet: 'dragon_purple',
      background: 'library',
    });
  });

  it('setEquipped updates a simple slot directly', () => {
    const store = useAvatarStore();
    store.setEquipped('outfit', 'warrior_armor');
    expect(store.equipped.outfit).toBe('warrior_armor');

    store.setEquipped('color', 'blue');
    expect(store.equipped.color).toBe('blue');

    store.setEquipped('pet', 'cat_white');
    expect(store.equipped.pet).toBe('cat_white');

    store.setEquipped('background', 'forest');
    expect(store.equipped.background).toBe('forest');
  });

  it('setEquipped on accessories appends items up to 2', () => {
    const store = useAvatarStore();
    expect(store.equipped.accessories).toEqual([]);

    store.setEquipped('accessories', 'hat_wizard');
    expect(store.equipped.accessories).toEqual(['hat_wizard']);

    store.setEquipped('accessories', 'badge_gold');
    expect(store.equipped.accessories).toEqual(['hat_wizard', 'badge_gold']);
  });

  it('setEquipped on accessories replaces the oldest item when already at max 2', () => {
    const store = useAvatarStore();
    store.setEquipped('accessories', 'hat_wizard');
    store.setEquipped('accessories', 'badge_gold');
    // Now at max, adding another should shift oldest and push new
    store.setEquipped('accessories', 'scarf_red');
    expect(store.equipped.accessories).toEqual(['badge_gold', 'scarf_red']);

    store.setEquipped('accessories', 'ring_magic');
    expect(store.equipped.accessories).toEqual(['scarf_red', 'ring_magic']);
  });

  it('hydrateFromEquipped maps server data to equipped slots', () => {
    const store = useAvatarStore();
    const serverEquipped = {
      color: { code: 'blue' },
      outfit: { code: 'mage_robes' },
      pet: { code: 'phoenix' },
      background: { code: 'volcano' },
    };

    store.hydrateFromEquipped(serverEquipped);

    expect(store.equipped.color).toBe('blue');
    expect(store.equipped.outfit).toBe('mage_robes');
    expect(store.equipped.pet).toBe('phoenix');
    expect(store.equipped.background).toBe('volcano');
  });

  it('hydrateFromEquipped is null/undefined safe', () => {
    const store = useAvatarStore();
    // Should not throw when called with missing/null slots
    store.hydrateFromEquipped({ color: null, outfit: undefined, pet: { code: 'wolf' } });
    expect(store.equipped.pet).toBe('wolf');
    // color and outfit remain default
    expect(store.equipped.color).toBe('purple');
    expect(store.equipped.outfit).toBe('student_robes');
  });
});
