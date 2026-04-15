import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AvatarTutor from '@/Components/Progress/AvatarTutor.vue';

// Mock Motion.js
vi.mock('motion', () => ({
  animate: vi.fn().mockResolvedValue({}),
}));

describe('AvatarTutor.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  it('renders avatar with idle state by default', () => {
    const wrapper = mount(AvatarTutor, {
      props: {},
    });
    expect(wrapper.find('.avatar-tutor').exists()).toBe(true);
    expect(wrapper.find('.state-idle').exists()).toBe(true);
    expect(wrapper.text()).toContain('🎓');
  });

  it('applies correct state class for explaining state', () => {
    const wrapper = mount(AvatarTutor, {
      props: { state: 'explaining' },
    });
    expect(wrapper.find('.state-explaining').exists()).toBe(true);
  });

  it('applies correct state class for thinking state', () => {
    const wrapper = mount(AvatarTutor, {
      props: { state: 'thinking' },
    });
    expect(wrapper.find('.state-thinking').exists()).toBe(true);
    // Should show ellipsis dots for thinking state
    expect(wrapper.find('.ellipsis-dots').exists()).toBe(true);
  });

  it('applies correct state class for celebrating state', () => {
    const wrapper = mount(AvatarTutor, {
      props: { state: 'celebrating' },
    });
    expect(wrapper.find('.state-celebrating').exists()).toBe(true);
  });

  it('emits interaction event when clicked', async () => {
    const wrapper = mount(AvatarTutor, {
      props: { state: 'idle' },
    });
    await wrapper.find('.avatar-tutor').trigger('click');
    expect(wrapper.emitted('interaction')).toHaveLength(1);
    expect(wrapper.emitted('interaction')[0][0]).toEqual({
      action: 'help',
      message: expect.any(String),
    });
  });

  it('shows help message when clicked', async () => {
    const wrapper = mount(AvatarTutor, {
      props: { state: 'idle' },
    });
    expect(wrapper.find('.help-bubble').exists()).toBe(false);
    await wrapper.find('.avatar-tutor').trigger('click');
    expect(wrapper.find('.help-bubble').exists()).toBe(true);
  });

  it('respects visible prop to show/hide avatar', () => {
    const wrapper = mount(AvatarTutor, {
      props: { visible: false },
    });
    expect(wrapper.find('.avatar-tutor').exists()).toBe(false);
  });

  it('accepts size prop (sm/md/lg)', () => {
    const wrapper = mount(AvatarTutor, {
      props: { size: 'lg' },
    });
    expect(wrapper.find('.size-lg').exists()).toBe(true);
  });
});
