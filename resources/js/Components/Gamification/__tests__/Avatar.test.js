import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Avatar from '../Avatar.vue';

function mockEquipped() {
  return {
    color: 'purple',
    outfit: 'student_robes',
    accessories: [],
    pet: 'dragon_purple'
  };
}

describe('Avatar.vue', () => {
  it('receives equipped, state, and size props', () => {
    const equipped = {
      color: 'purple',
      outfit: 'student_robes',
      accessories: [],
      pet: 'dragon_purple',
      background: 'library'
    };
    const { vm } = mount(Avatar, {
      props: { equipped, state: 'happy', size: 'lg' }
    });
    expect(vm.equipped).toEqual(equipped);
    expect(vm.state).toBe('happy');
    expect(vm.size).toBe('lg');
  });

  it('applies correct CSS width/height for each size', () => {
    const sizes = {
      sm: ['60px', '80px'],
      md: ['120px', '140px'],
      lg: ['200px', '240px']
    };

    for (const [size, [width, height]] of Object.entries(sizes)) {
      const wrapper = mount(Avatar, {
        props: {
          equipped: mockEquipped(),
          size
        }
      });
      const svg = wrapper.find('svg').element;
      expect(svg.style.width).toBe(width);
      expect(svg.style.height).toBe(height);
    }
  });

  it('applies animation state class to SVG', () => {
    const states = ['idle', 'happy', 'tired', 'thinking'];

    for (const state of states) {
      const wrapper = mount(Avatar, {
        props: {
          equipped: mockEquipped(),
          state
        }
      });
      const svg = wrapper.find('svg').element;
      expect(svg.classList.contains(`avatar-${state}`)).toBe(true);
    }
  });

  it('defaults to state=idle and size=md when not provided', () => {
    const { vm } = mount(Avatar, {
      props: { equipped: mockEquipped() }
    });
    expect(vm.state).toBe('idle');
    expect(vm.size).toBe('md');
  });

  it('renders SVG with cosmetics data attributes for testing', () => {
    const equipped = {
      color: 'golden',
      outfit: 'wizard_robes',
      accessories: ['glasses'],
      pet: 'owl_brown'
    };
    const wrapper = mount(Avatar, {
      props: { equipped, size: 'md' }
    });
    const svg = wrapper.find('svg').element;
    expect(svg.getAttribute('data-color')).toBe('golden');
    expect(svg.getAttribute('data-outfit')).toBe('wizard_robes');
    expect(svg.getAttribute('data-pet')).toBe('owl_brown');
  });

  it('renders SVG with data-accessories attribute correctly', () => {
    const equipped = {
      color: 'blue',
      outfit: 'casual',
      accessories: ['scarf', 'hat', 'gloves'],
      pet: 'cat_white'
    };
    const wrapper = mount(Avatar, {
      props: { equipped, size: 'md' }
    });
    const svg = wrapper.find('svg').element;
    expect(svg.getAttribute('data-accessories')).toBe('scarf,hat,gloves');
  });

  it('renders SVG with empty data-accessories when no accessories', () => {
    const equipped = {
      color: 'red',
      outfit: 'formal',
      accessories: [],
      pet: 'phoenix_red'
    };
    const wrapper = mount(Avatar, {
      props: { equipped, size: 'md' }
    });
    const svg = wrapper.find('svg').element;
    expect(svg.getAttribute('data-accessories')).toBe('');
  });

  it('renders SVG with proper viewBox and structure', () => {
    const wrapper = mount(Avatar, {
      props: { equipped: mockEquipped(), size: 'md' }
    });
    const svg = wrapper.find('svg').element;
    expect(svg).toBeTruthy();
    expect(svg.getAttribute('viewBox')).toBe('0 0 140 160');
    expect(svg.querySelector('g')).toBeTruthy();
  });
});
