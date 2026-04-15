// resources/js/__tests__/ProgressBar.spec.js
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgressBar from '@/Components/Progress/ProgressBar.vue';

describe('ProgressBar.vue', () => {
  it('renders with correct percentage', () => {
    const wrapper = mount(ProgressBar, {
      props: { percentage: 50 },
    });
    const fill = wrapper.find('[data-testid="progress-fill"]');
    expect(fill.attributes('style')).toContain('width: 50%');
  });

  it('displays label and percentage', () => {
    const wrapper = mount(ProgressBar, {
      props: {
        percentage: 75,
        label: 'Progress',
      },
    });
    expect(wrapper.text()).toContain('Progress');
    expect(wrapper.text()).toContain('75%');
  });

  it('displays sublabel when provided', () => {
    const wrapper = mount(ProgressBar, {
      props: {
        sublabel: '20 points to next level',
      },
    });
    expect(wrapper.text()).toContain('20 points to next level');
  });

  it('validates percentage is between 0-100', () => {
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
    mount(ProgressBar, { props: { percentage: 150 } });
    expect(warnSpy).toHaveBeenCalled();
    warnSpy.mockRestore();
  });

  it('applies custom height class', () => {
    const wrapper = mount(ProgressBar, {
      props: {
        percentage: 50,
        height: 'h-4',
      },
    });
    const container = wrapper.find('[data-testid="progress-container"]');
    expect(container.classes()).toContain('h-4');
  });
});
