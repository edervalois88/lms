import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AvatarDialog from '@/Components/Progress/AvatarDialog.vue';

describe('AvatarDialog.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  it('renders dialog when open prop is true', () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: true,
      },
    });
    expect(wrapper.find('.avatar-dialog-overlay').exists()).toBe(true);
    expect(wrapper.find('.avatar-dialog-panel').exists()).toBe(true);
  });

  it('hides dialog when open prop is false', () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: false,
      },
    });
    expect(wrapper.find('.avatar-dialog-overlay').exists()).toBe(false);
    expect(wrapper.find('.avatar-dialog-panel').exists()).toBe(false);
  });

  it('displays all 4 dialog options', () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: true,
      },
    });
    const options = wrapper.findAll('.dialog-option');
    expect(options).toHaveLength(4);

    // Check for specific option texts
    expect(wrapper.text()).toContain('Dame un tip');
    expect(wrapper.text()).toContain('Explícame esto');
    expect(wrapper.text()).toContain('¿Qué sigue?');
    expect(wrapper.text()).toContain('Cuéntame un chiste');
  });

  it('emits action event with correct action when option clicked', async () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: true,
      },
    });

    const options = wrapper.findAll('.dialog-option');
    await options[0].trigger('click');

    expect(wrapper.emitted('action')).toHaveLength(1);
    expect(wrapper.emitted('action')[0][0]).toBe('tip');
  });

  it('emits close event when clicking outside (overlay)', async () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: true,
      },
    });

    await wrapper.find('.avatar-dialog-overlay').trigger('click');

    expect(wrapper.emitted('close')).toHaveLength(1);
  });

  it('auto-closes after 5 seconds', async () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: true,
      },
    });

    expect(wrapper.emitted('close')).toBeUndefined();

    // Advance timers by 5 seconds
    vi.advanceTimersByTime(5000);
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted('close')).toBeTruthy();
    expect(wrapper.emitted('close')).toHaveLength(1);
  });

  it('emits correct action for each option', async () => {
    const wrapper = mount(AvatarDialog, {
      props: {
        open: true,
      },
    });

    const options = wrapper.findAll('.dialog-option');
    const expectedActions = ['tip', 'explain', 'roadmap', 'joke'];

    for (let i = 0; i < options.length; i++) {
      await options[i].trigger('click');
    }

    const emittedActions = wrapper.emitted('action');
    expect(emittedActions).toHaveLength(4);
    emittedActions.forEach((emission, index) => {
      expect(emission[0]).toBe(expectedActions[index]);
    });
  });

  it('accepts different contexts (quiz, dashboard, simulator)', async () => {
    const contexts = ['quiz', 'dashboard', 'simulator'];

    for (const context of contexts) {
      const wrapper = mount(AvatarDialog, {
        props: {
          open: true,
          context,
        },
      });

      // Verify dialog renders with the context prop
      expect(wrapper.find('.avatar-dialog-panel').exists()).toBe(true);
      expect(wrapper.vm.context).toBe(context);
    }
  });
});
