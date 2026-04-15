// resources/js/__tests__/AvatarShowcase.spec.js
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AvatarShowcase from '../Components/Progress/AvatarShowcase.vue';

describe('AvatarShowcase.vue', () => {
  it('renders the avatar component', () => {
    const wrapper = mount(AvatarShowcase, {
      props: {
        icon: '🎓',
        cosmetics: { ropa: 'Uniforme', accesorios: 'Gafas', color: 'Azul' },
      },
      global: {
        stubs: { AvatarAnimated: true }
      }
    });
    expect(wrapper.findComponent({ name: 'AvatarAnimated' }).exists()).toBe(true);
  });

  it('displays all three cosmetic labels', () => {
    const wrapper = mount(AvatarShowcase, {
      props: {
        icon: '🎓',
        cosmetics: { ropa: 'Uniforme', accesorios: 'Gafas', color: 'Azul' },
      },
      global: {
        stubs: { AvatarAnimated: true }
      }
    });
    expect(wrapper.text()).toContain('Uniforme');
    expect(wrapper.text()).toContain('Gafas');
    expect(wrapper.text()).toContain('Azul');
  });

  it('shows rewards link when rewardsRoute is provided', () => {
    const wrapper = mount(AvatarShowcase, {
      props: {
        icon: '🎓',
        cosmetics: { ropa: 'Básica', accesorios: 'Ninguno', color: 'Morado' },
        rewardsRoute: '/rewards',
      },
      global: {
        stubs: { AvatarAnimated: true }
      }
    });
    const link = wrapper.find('a[href="/rewards"]');
    expect(link.exists()).toBe(true);
    expect(link.text()).toContain('Rewards');
  });

  it('hides rewards link when rewardsRoute is null', () => {
    const wrapper = mount(AvatarShowcase, {
      props: {
        icon: '🎓',
        cosmetics: { ropa: 'Básica', accesorios: 'Ninguno', color: 'Morado' },
        rewardsRoute: null,
      },
      global: {
        stubs: { AvatarAnimated: true }
      }
    });
    expect(wrapper.find('a').exists()).toBe(false);
  });

  it('uses xl size for AvatarAnimated', () => {
    const wrapper = mount(AvatarShowcase, {
      props: {
        icon: '👤',
        cosmetics: { ropa: 'Básica', accesorios: 'Ninguno', color: 'Morado' },
      },
      global: {
        stubs: { AvatarAnimated: true }
      }
    });
    const avatar = wrapper.findComponent({ name: 'AvatarAnimated' });
    expect(avatar.props('size')).toBe('xl');
  });
});
