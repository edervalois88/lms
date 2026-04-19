import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAvatarStore } from '../avatarStore.js';

describe('useAvatarStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with default cosmetics', () => {
    const store = useAvatarStore();
    expect(store.equipped.color).toBe('purple');
    expect(store.equipped.outfit).toBe('student_robes');
    expect(store.equipped.pet).toBe('dragon_purple');
    expect(store.equipped.background).toBe('library');
    expect(store.equipped.accessories).toEqual([]);
  });

  it('sets equipped cosmetic for non-accessory slots', () => {
    const store = useAvatarStore();
    store.setEquipped('outfit', 'wizard_robes');
    expect(store.equipped.outfit).toBe('wizard_robes');
  });

  it('appends accessories up to 2', () => {
    const store = useAvatarStore();
    store.setEquipped('accessories', 'glasses');
    store.setEquipped('accessories', 'crown');
    expect(store.equipped.accessories).toEqual(['glasses', 'crown']);
  });

  it('removes oldest accessory when adding 3rd', () => {
    const store = useAvatarStore();
    store.setEquipped('accessories', 'glasses');
    store.setEquipped('accessories', 'crown');
    store.setEquipped('accessories', 'backpack');
    expect(store.equipped.accessories).toEqual(['crown', 'backpack']);
  });

  it('hydrates from server response with all fields', () => {
    const store = useAvatarStore();
    const serverData = {
      color: { code: 'golden' },
      outfit: { code: 'lab_coat' },
      pet: { code: 'owl_brown' },
      background: { code: 'forest' },
      accessories: [{ code: 'glasses' }, { code: 'scarf' }],
    };
    store.hydrateFromEquipped(serverData);
    expect(store.equipped.color).toBe('golden');
    expect(store.equipped.outfit).toBe('lab_coat');
    expect(store.equipped.pet).toBe('owl_brown');
    expect(store.equipped.background).toBe('forest');
    expect(store.equipped.accessories).toEqual(['glasses', 'scarf']);
  });

  it('handles null server response gracefully', () => {
    const store = useAvatarStore();
    const originalEquipped = { ...store.equipped };
    store.hydrateFromEquipped(null);
    expect(store.equipped).toEqual(originalEquipped);
  });

  it('enforces max 2 accessories when hydrating from server with 3+', () => {
    const store = useAvatarStore();
    const serverData = {
      color: { code: 'golden' },
      outfit: { code: 'lab_coat' },
      pet: { code: 'owl_brown' },
      background: { code: 'forest' },
      accessories: [{ code: 'glasses' }, { code: 'crown' }, { code: 'backpack' }],
    };
    store.hydrateFromEquipped(serverData);
    // Should only have first 2
    expect(store.equipped.accessories).toEqual(['glasses', 'crown']);
    expect(store.equipped.accessories.length).toBe(2);
  });
});
