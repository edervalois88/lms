import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import Avatar from '@/Components/Gamification/Avatar.vue';
import { useAvatarStore } from '@/Stores/gamification/avatarStore.js';
import { COSMETIC_DEFINITIONS } from '@/Data/cosmetics.js';

describe('Avatar Integration Tests', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('renders avatar with store-provided equipped cosmetics', () => {
    const store = useAvatarStore();
    store.setEquipped('outfit', 'wizard_robes');
    store.setEquipped('accessories', 'crown');

    const wrapper = mount(Avatar, {
      props: { equipped: store.equipped },
    });

    expect(wrapper.vm.equipped.outfit).toBe('wizard_robes');
    expect(wrapper.vm.equipped.accessories).toContain('crown');
  });

  it('updates avatar when store changes', async () => {
    const store = useAvatarStore();
    const wrapper = mount(Avatar, {
      props: { equipped: store.equipped },
    });

    expect(wrapper.vm.equipped.color).toBe('purple');

    store.setEquipped('color', 'golden');
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.equipped.color).toBe('golden');
  });

  it('displays different states with correct animations', async () => {
    const defaultEquipped = {
      color: 'purple',
      outfit: 'student_robes',
      accessories: [],
      pet: 'dragon_purple',
      background: 'library',
    };

    const wrapper = mount(Avatar, {
      props: { equipped: defaultEquipped, state: 'idle' },
    });

    expect(wrapper.find('.avatar-idle').exists()).toBe(true);

    await wrapper.setProps({ state: 'happy' });
    expect(wrapper.find('.avatar-happy').exists()).toBe(true);

    await wrapper.setProps({ state: 'tired' });
    expect(wrapper.find('.avatar-tired').exists()).toBe(true);

    await wrapper.setProps({ state: 'thinking' });
    expect(wrapper.find('.avatar-thinking').exists()).toBe(true);
  });

  it('renders correctly at all sizes', () => {
    const defaultEquipped = {
      color: 'purple',
      outfit: 'student_robes',
      accessories: [],
      pet: 'dragon_purple',
      background: 'library',
    };

    const sizes = ['sm', 'md', 'lg'];

    sizes.forEach((size) => {
      const wrapper = mount(Avatar, { props: { equipped: defaultEquipped, size } });
      expect(wrapper.find('svg').exists()).toBe(true);
    });
  });

  it('applies cosmetic definitions correctly from store hydration', async () => {
    const store = useAvatarStore();

    // Simulate server hydration
    const serverData = {
      color: { code: 'golden' },
      outfit: { code: 'wizard_robes' },
      pet: { code: 'dragon_purple' },
      background: { code: 'forest' },
      accessories: [{ code: 'crown' }],
    };

    store.hydrateFromEquipped(serverData);

    const wrapper = mount(Avatar, {
      props: { equipped: store.equipped },
    });

    expect(wrapper.vm.colorStyle.color).toBe('#f4d03f'); // golden
    expect(wrapper.vm.outfitStyle.color).toBe('#3b82f6'); // wizard_robes
    expect(wrapper.vm.petStyle.color).toBe('#a855f7'); // dragon_purple
  });
});
