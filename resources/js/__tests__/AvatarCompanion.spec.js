import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AvatarCompanion from '@/Components/Progress/AvatarCompanion.vue';

// Mock the composable
vi.mock('@/Composables/useProgressAnimation', () => ({
  useProgressAnimation: () => ({
    animateAvatarWave: vi.fn(),
  }),
}));

describe('AvatarCompanion.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  it('renders avatar with default icon', () => {
    const wrapper = mount(AvatarCompanion, {
      props: { icon: '👤' },
      global: {
        components: {
          AvatarAnimated: { template: '<div>Avatar</div>' },
        },
      },
    });
    expect(wrapper.find('.avatar-click').exists()).toBe(true);
  });

  it('shows message on click', async () => {
    const wrapper = mount(AvatarCompanion, {
      props: { icon: '👤', context: 'default' },
      global: {
        components: {
          AvatarAnimated: { template: '<div>Avatar</div>' },
        },
      },
    });
    await wrapper.find('.avatar-click').trigger('click');
    expect(wrapper.vm.showMessage).toBe(true);
    expect(wrapper.vm.message).toBeTruthy();
  });

  it('emits interaction event on click', async () => {
    const wrapper = mount(AvatarCompanion, {
      props: { icon: '👤' },
      global: {
        components: {
          AvatarAnimated: { template: '<div>Avatar</div>' },
        },
      },
    });
    await wrapper.find('.avatar-click').trigger('click');
    expect(wrapper.emitted('interaction')).toHaveLength(1);
    expect(wrapper.emitted('interaction')[0][0].clickCount).toBe(1);
  });

  it('changes state to tired after many clicks', async () => {
    const wrapper = mount(AvatarCompanion, {
      props: { icon: '👤' },
      global: {
        components: {
          AvatarAnimated: { template: '<div>Avatar</div>' },
        },
      },
    });
    const avatar = wrapper.find('.avatar-click');
    for (let i = 0; i < 5; i++) {
      await avatar.trigger('click');
    }
    expect(wrapper.vm.avatarState).toBe('tired');
  });
});
