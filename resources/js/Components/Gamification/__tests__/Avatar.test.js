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

  // Test 7: Renders SVG with cosmetic colors
  it('renders SVG with cosmetic color style', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'golden',
          outfit: 'wizard_robes',
          accessories: [],
          pet: 'dragon_purple'
        }
      }
    });

    const headPolygon = wrapper.element.querySelector('polygon[points="70,15 95,32 95,65 70,82 45,65 45,32"]');
    expect(headPolygon).toBeTruthy();
    expect(headPolygon.getAttribute('fill')).toBe('#f4d03f'); // golden color
  });

  // Test 8: Renders outfit overlay when equipped
  it('renders outfit layer with correct color', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'purple',
          outfit: 'wizard_robes',
          accessories: [],
          pet: 'dragon_purple'
        }
      }
    });

    const outfitGroup = wrapper.element.querySelector('#avatar-outfit');
    expect(outfitGroup).toBeTruthy();

    const outfitPolygon = outfitGroup.querySelector('polygon');
    expect(outfitPolygon.getAttribute('fill')).toBe('#3b82f6'); // wizard_robes color
  });

  // Test 9: Renders accessories conditionally
  it('renders accessories based on equipped list', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'purple',
          outfit: 'student_robes',
          accessories: ['glasses', 'crown'],
          pet: 'dragon_purple'
        }
      }
    });

    const accessories = wrapper.element.querySelectorAll('#avatar-accessory');
    expect(accessories.length).toBe(2);
  });

  // Test 10: Renders pet at shoulder
  it('renders pet circle at shoulder position', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'purple',
          outfit: 'student_robes',
          accessories: [],
          pet: 'owl_brown'
        }
      }
    });

    const petGroup = wrapper.element.querySelector('#avatar-pet');
    expect(petGroup).toBeTruthy();

    const petCircle = petGroup.querySelector('circle');
    expect(petCircle.getAttribute('cx')).toBe('100');
    expect(petCircle.getAttribute('cy')).toBe('85');
  });

  // Test 11: Falls back to default cosmetics for missing codes
  it('renders with default cosmetics when codes are invalid', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'invalid_code',
          outfit: 'nonexistent',
          accessories: [],
          pet: 'fake_pet'
        }
      }
    });

    const svg = wrapper.find('svg').element;
    expect(svg).toBeTruthy();

    // Should render with default purple color
    const headPolygon = svg.querySelector('polygon[points="70,15 95,32 95,65 70,82 45,65 45,32"]');
    expect(headPolygon.getAttribute('fill')).toBe('#d4a574'); // default purple color
  });

  // Test 12: Renders outfit details (stars, emblem)
  it('renders outfit details based on outfit type', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'purple',
          outfit: 'wizard_robes',
          accessories: [],
          pet: 'dragon_purple'
        }
      }
    });

    const outfitGroup = wrapper.element.querySelector('#avatar-outfit');
    const stars = outfitGroup.querySelectorAll('circle[fill="#ffd700"]');

    // Wizard robes have stars (hasStars detail)
    expect(stars.length).toBeGreaterThan(0);
  });
});
