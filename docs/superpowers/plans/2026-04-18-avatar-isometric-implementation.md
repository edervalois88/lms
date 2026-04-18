# Avatar SVG Isométrico - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a dynamic, parameterized SVG component that renders isometric 3D avatars in multiple sizes with four animation states, fully integrated with the cosmetics/rewards system.

**Architecture:** One Vue component (`Avatar.vue`) receives `equipped` cosmetics and `state` props, computes visual properties from a centralized cosmetics definitions file (`cosmetics.js`), and renders layered SVG (body → outfit → accessories → pet) with CSS animations. The component scales losslessly across three sizes (sm/md/lg) via SVG viewBox. Four animation states (idle, happy, tired, thinking) are applied via CSS keyframe classes.

**Tech Stack:** Vue 3, SVG native, CSS animations, Pinia (existing), Vitest for unit tests

---

## Task 1: Create cosmetics.js Data File

**Files:**
- Create: `resources/js/Data/cosmetics.js`
- Reference: `docs/superpowers/specs/2026-04-18-avatar-isometric-design.md`

- [ ] **Step 1: Write cosmetics.js with all cosmetic definitions**

Create `resources/js/Data/cosmetics.js`:

```javascript
export const COSMETIC_DEFINITIONS = {
  colors: {
    purple: {
      name: 'Púrpura Claro',
      skinTone: '#d4a574',
      code: 'purple',
    },
    golden: {
      name: 'Dorado',
      skinTone: '#f4d03f',
      code: 'golden',
    },
    fair: {
      name: 'Piel Clara',
      skinTone: '#f5deb3',
      code: 'fair',
    },
    tan: {
      name: 'Bronceado',
      skinTone: '#d2b48c',
      code: 'tan',
    },
  },

  outfits: {
    student_robes: {
      name: 'Túnica Estudiante',
      code: 'student_robes',
      color: '#9333ea',
      details: {
        hasStripes: false,
        emblem: 'book',
      },
    },
    wizard_robes: {
      name: 'Robes Mágico',
      code: 'wizard_robes',
      color: '#3b82f6',
      details: {
        hasStars: true,
        glowEffect: true,
      },
    },
    casual_shirt: {
      name: 'Camisa Casual',
      code: 'casual_shirt',
      color: '#ef4444',
      details: {
        hasStripes: true,
        emblem: null,
      },
    },
    lab_coat: {
      name: 'Bata de Laboratorio',
      code: 'lab_coat',
      color: '#ffffff',
      details: {
        hasStripes: false,
        emblem: 'flask',
      },
    },
  },

  accessories: {
    glasses: {
      name: 'Gafas',
      code: 'glasses',
      type: 'glasses',
      color: '#1f2937',
      size: 'small',
    },
    backpack: {
      name: 'Mochila',
      code: 'backpack',
      type: 'backpack',
      color: '#7c3aed',
      size: 'large',
    },
    crown: {
      name: 'Corona',
      code: 'crown',
      type: 'crown',
      color: '#ffd700',
      size: 'small',
    },
    scarf: {
      name: 'Bufanda',
      code: 'scarf',
      type: 'scarf',
      color: '#dc2626',
      size: 'medium',
    },
  },

  pets: {
    dragon_purple: {
      name: 'Dragón Púrpura',
      code: 'dragon_purple',
      type: 'dragon',
      color: '#a855f7',
      shape: 'circle',
    },
    owl_brown: {
      name: 'Búho Marrón',
      code: 'owl_brown',
      type: 'owl',
      color: '#92400e',
      shape: 'circle',
    },
    phoenix_gold: {
      name: 'Fénix Dorado',
      code: 'phoenix_gold',
      type: 'phoenix',
      color: '#fbbf24',
      shape: 'circle',
    },
    fox_red: {
      name: 'Zorro Rojo',
      code: 'fox_red',
      type: 'fox',
      color: '#dc2626',
      shape: 'circle',
    },
  },

  backgrounds: {
    library: {
      name: 'Biblioteca',
      code: 'library',
      color: '#92400e',
      pattern: 'books',
    },
    forest: {
      name: 'Bosque',
      code: 'forest',
      color: '#15803d',
      pattern: 'trees',
    },
    starfield: {
      name: 'Campo de Estrellas',
      code: 'starfield',
      color: '#1e1b4b',
      pattern: 'stars',
    },
  },
};

// Helper function to get default cosmetic
export function getDefaultCosmetic(type) {
  const defaults = {
    color: 'purple',
    outfit: 'student_robes',
    pet: 'dragon_purple',
    background: 'library',
  };
  return defaults[type] || null;
}
```

- [ ] **Step 2: Verify cosmetics.js exports correctly**

Run in terminal:
```bash
node -e "import('./resources/js/Data/cosmetics.js').then(m => console.log(Object.keys(m.COSMETIC_DEFINITIONS)))"
```

Expected: Prints `[ 'colors', 'outfits', 'accessories', 'pets', 'backgrounds' ]`

- [ ] **Step 3: Commit**

```bash
git add resources/js/Data/cosmetics.js
git commit -m "feat: add cosmetics definitions for avatar system

Centralized data file with all cosmetic properties:
- 4 color options (skin tones)
- 4 outfit styles with details
- 4 accessories with sizes
- 4 pets with shapes
- 3 backgrounds with patterns

Each cosmetic has code, name, and visual properties."
```

---

## Task 2: Create Avatar.vue Component (Base Structure)

**Files:**
- Create: `resources/js/Components/Gamification/Avatar.vue`
- Reference: `resources/js/Data/cosmetics.js`, spec section "Isometric SVG Structure"

- [ ] **Step 1: Write Avatar.vue component skeleton with props**

Create `resources/js/Components/Gamification/Avatar.vue`:

```vue
<script setup>
import { computed } from 'vue';
import { COSMETIC_DEFINITIONS, getDefaultCosmetic } from '@/Data/cosmetics.js';

const props = defineProps({
  equipped: {
    type: Object,
    default: () => ({
      color: getDefaultCosmetic('color'),
      outfit: getDefaultCosmetic('outfit'),
      accessories: [],
      pet: getDefaultCosmetic('pet'),
      background: getDefaultCosmetic('background'),
    }),
  },
  state: {
    type: String,
    default: 'idle',
    validator: (val) => ['idle', 'happy', 'tired', 'thinking'].includes(val),
  },
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['sm', 'md', 'lg'].includes(val),
  },
});

// Size configuration
const sizeConfig = computed(() => {
  const sizes = {
    sm: { width: 60, height: 80 },
    md: { width: 120, height: 140 },
    lg: { width: 200, height: 240 },
  };
  return sizes[props.size];
});

// Get cosmetic properties with fallback
const getCosmetic = (type, code) => {
  if (!code) return null;
  const cosmetic = COSMETIC_DEFINITIONS[type]?.[code];
  return cosmetic || null;
};

// Compute cosmetic styles
const colorStyle = computed(() => getCosmetic('colors', props.equipped.color) || {});
const outfitStyle = computed(() => getCosmetic('outfits', props.equipped.outfit) || {});
const petStyle = computed(() => getCosmetic('pets', props.equipped.pet) || {});

const accessoryStyles = computed(() => {
  return (props.equipped.accessories || []).map((code) =>
    getCosmetic('accessories', code)
  ).filter(Boolean);
});
</script>

<template>
  <div :class="['avatar', `state-${state}`, `size-${size}`]">
    <svg
      :width="sizeConfig.width"
      :height="sizeConfig.height"
      viewBox="0 0 140 160"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- Avatar will be rendered here -->
      <!-- Placeholder for now -->
      <rect x="10" y="10" width="120" height="140" fill="#e5e7eb" stroke="#9ca3af" stroke-width="1" />
      <text x="70" y="85" text-anchor="middle" font-size="12" fill="#6b7280">
        Avatar SVG
      </text>
    </svg>
  </div>
</template>

<style scoped>
.avatar {
  display: inline-block;
  overflow: hidden;
}

/* Animation states */
.state-idle {
  animation: idle-float 3s ease-in-out infinite;
}

.state-happy {
  animation: happy-bounce 0.6s ease-out;
}

.state-tired {
  opacity: 0.7;
  filter: grayscale(20%);
}

.state-thinking {
  animation: thinking-sway 2s ease-in-out infinite;
}

@keyframes idle-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

@keyframes happy-bounce {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

@keyframes thinking-sway {
  0%, 100% { transform: rotateZ(-2deg); }
  50% { transform: rotateZ(2deg); }
}
</style>
```

- [ ] **Step 2: Create test file for Avatar component**

Create `tests/Unit/Components/AvatarTest.js`:

```javascript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Avatar from '@/Components/Gamification/Avatar.vue';

describe('Avatar.vue', () => {
  it('renders with default props', () => {
    const wrapper = mount(Avatar);
    expect(wrapper.find('.avatar').exists()).toBe(true);
    expect(wrapper.find('svg').exists()).toBe(true);
  });

  it('applies size class correctly', () => {
    const wrapper = mount(Avatar, { props: { size: 'lg' } });
    expect(wrapper.find('.size-lg').exists()).toBe(true);
  });

  it('applies state class correctly', () => {
    const wrapper = mount(Avatar, { props: { state: 'happy' } });
    expect(wrapper.find('.state-happy').exists()).toBe(true);
  });

  it('validates state prop', () => {
    const wrapper = mount(Avatar, { props: { state: 'invalid' } });
    // Should default to 'idle' due to validator
    expect(wrapper.classes()).toContain('state-idle');
  });

  it('validates size prop', () => {
    const wrapper = mount(Avatar, { props: { size: 'invalid' } });
    // Should default to 'md' due to validator
    expect(wrapper.classes()).toContain('size-md');
  });

  it('receives equipped prop with cosmetics codes', () => {
    const equipped = {
      color: 'purple',
      outfit: 'student_robes',
      accessories: ['glasses'],
      pet: 'dragon_purple',
      background: 'library',
    };
    const wrapper = mount(Avatar, { props: { equipped } });
    expect(wrapper.vm.equipped).toEqual(equipped);
  });
});
```

- [ ] **Step 3: Run tests to verify they pass**

```bash
npm run test -- tests/Unit/Components/AvatarTest.js
```

Expected: All 6 tests pass

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Gamification/Avatar.vue tests/Unit/Components/AvatarTest.js
git commit -m "feat: add Avatar component skeleton with props and tests

Scaffold Avatar.vue with:
- Props: equipped (cosmetics), state (idle/happy/tired/thinking), size (sm/md/lg)
- Computed properties for size configuration
- Helper methods to retrieve cosmetic definitions
- CSS animations for all four states (idle-float, happy-bounce, tired, thinking-sway)
- Placeholder SVG rendering
- Unit tests covering props validation and rendering"
```

---

## Task 3: Implement Isometric SVG Rendering

**Files:**
- Modify: `resources/js/Components/Gamification/Avatar.vue`
- Reference: spec section "Isometric SVG Structure"

- [ ] **Step 1: Update Avatar.vue template with SVG isometric avatar**

Replace the placeholder SVG in the template with complete isometric avatar:

```vue
<template>
  <div :class="['avatar', `state-${state}`, `size-${size}`]">
    <svg
      :width="sizeConfig.width"
      :height="sizeConfig.height"
      viewBox="0 0 140 160"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- Base body group -->
      <g class="avatar-body">
        <!-- Head (isometric polygon) -->
        <polygon
          points="70,20 85,30 85,50 70,60 55,50 55,30"
          :fill="colorStyle.skinTone || '#d4a574'"
          stroke="#5a3d2a"
          stroke-width="1"
        />

        <!-- Eyes -->
        <circle cx="65" cy="40" r="2.5" fill="#2c1810" />
        <circle cx="75" cy="40" r="2.5" fill="#2c1810" />
        <circle cx="66" cy="38.5" r="1" fill="#ffffff" opacity="0.7" />
        <circle cx="76" cy="38.5" r="1" fill="#ffffff" opacity="0.7" />

        <!-- Mouth -->
        <path d="M 68 48 Q 70 51 72 48" stroke="#5a3d2a" stroke-width="1" fill="none" stroke-linecap="round" />
      </g>

      <!-- Torso (base outfit layer) -->
      <g class="avatar-torso">
        <polygon
          points="55,60 70,70 85,60 85,85 70,95 55,85"
          :fill="outfitStyle.color || '#9333ea'"
          stroke="#5a3d2a"
          stroke-width="1"
        />
      </g>

      <!-- Arms -->
      <g class="avatar-arms">
        <polygon
          points="30,75 55,88 55,100 30,87"
          :fill="colorStyle.skinTone || '#d4a574'"
          stroke="#5a3d2a"
          stroke-width="1"
        />
        <polygon
          points="85,88 110,75 110,87 85,100"
          :fill="colorStyle.skinTone || '#d4a574'"
          stroke="#5a3d2a"
          stroke-width="1"
        />
      </g>

      <!-- Legs -->
      <g class="avatar-legs">
        <polygon
          points="55,85 65,90 65,115 55,110"
          fill="#2c1810"
          stroke="#000000"
          stroke-width="1"
        />
        <polygon
          points="70,95 80,90 80,115 70,120"
          fill="#2c1810"
          stroke="#000000"
          stroke-width="1"
        />
      </g>

      <!-- Outfit details (conditional) -->
      <g v-if="outfitStyle.details?.emblem" class="outfit-emblem">
        <circle cx="70" cy="75" r="3" :fill="outfitStyle.color || '#9333ea'" opacity="0.7" />
      </g>

      <!-- Accessories (up to 2) -->
      <g v-for="(accessory, idx) in accessoryStyles" :key="idx" class="avatar-accessory">
        <g v-if="accessory.type === 'glasses'">
          <!-- Glasses: two small circles on face -->
          <circle cx="62" cy="38" r="3" fill="none" :stroke="accessory.color" stroke-width="1" />
          <circle cx="78" cy="38" r="3" fill="none" :stroke="accessory.color" stroke-width="1" />
          <line x1="65" y1="38" x2="75" y2="38" :stroke="accessory.color" stroke-width="1" />
        </g>
        <g v-if="accessory.type === 'crown'">
          <!-- Crown: triangle on head -->
          <polygon
            points="70,15 75,25 65,25"
            :fill="accessory.color"
            :stroke="accessory.color"
            stroke-width="1"
          />
          <circle cx="70" cy="22" r="1.5" :fill="accessory.color" />
        </g>
        <g v-if="accessory.type === 'backpack'">
          <!-- Backpack: rectangle on back -->
          <rect
            x="58"
            y="68"
            width="12"
            height="16"
            :fill="accessory.color"
            :stroke="accessory.color"
            stroke-width="1"
          />
        </g>
        <g v-if="accessory.type === 'scarf'">
          <!-- Scarf: curved lines around neck -->
          <path
            d="M 58 58 Q 60 62 68 62 Q 76 62 78 58"
            :stroke="accessory.color"
            stroke-width="2"
            fill="none"
            stroke-linecap="round"
          />
        </g>
      </g>

      <!-- Pet (small circle at shoulder) -->
      <g v-if="petStyle" class="avatar-pet">
        <circle cx="100" cy="80" r="8" :fill="petStyle.color" :stroke="petStyle.color" stroke-width="1" />
        <!-- Pet eye -->
        <circle cx="102" cy="78" r="2" fill="#000000" />
      </g>
    </svg>
  </div>
</template>
```

- [ ] **Step 2: Run tests to ensure no regressions**

```bash
npm run test -- tests/Unit/Components/AvatarTest.js
```

Expected: All 6 tests still pass (no new tests, just verification)

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Gamification/Avatar.vue
git commit -m "feat: implement isometric SVG avatar rendering

Complete SVG rendering with:
- Isometric body (head, torso, arms, legs)
- Dynamic skin tone from equipped.color
- Dynamic outfit color from equipped.outfit
- Conditional outfit details (emblem, stars, etc)
- Accessory rendering (glasses, crown, backpack, scarf)
- Pet rendering at shoulder with dynamic color
- All elements conditionally rendered based on equipped props"
```

---

## Task 4: Add Avatar State Animations

**Files:**
- Modify: `resources/js/Components/Gamification/Avatar.vue`
- Reference: spec section "Animation States"

- [ ] **Step 1: Update Avatar.vue styles with complete animations**

Update the `<style scoped>` section to include all animation keyframes:

```vue
<style scoped>
.avatar {
  display: inline-block;
  overflow: hidden;
  transition: all 0.3s ease;
}

/* State animations */
.state-idle {
  animation: idle-float 3s ease-in-out infinite;
}

.state-happy {
  animation: happy-bounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

.state-tired {
  opacity: 0.7;
  filter: grayscale(0.2) brightness(0.95);
}

.state-thinking {
  animation: thinking-sway 2s ease-in-out infinite;
}

/* Keyframe animations */
@keyframes idle-float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-8px);
  }
}

@keyframes happy-bounce {
  0% {
    transform: scale(1) translateY(0);
  }
  25% {
    transform: scale(1.1) translateY(-12px);
  }
  50% {
    transform: scale(1.15) translateY(-20px);
  }
  100% {
    transform: scale(1) translateY(0);
  }
}

@keyframes thinking-sway {
  0%, 100% {
    transform: rotateZ(-2deg);
  }
  25% {
    transform: rotateZ(-2deg) translateX(-2px);
  }
  50% {
    transform: rotateZ(2deg) translateX(2px);
  }
  75% {
    transform: rotateZ(2deg) translateX(-2px);
  }
}

/* Size responsive */
.size-sm svg {
  filter: drop-shadow(1px 1px 2px rgba(0, 0, 0, 0.2));
}

.size-md svg {
  filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
}

.size-lg svg {
  filter: drop-shadow(3px 3px 6px rgba(0, 0, 0, 0.4));
}
</style>
```

- [ ] **Step 2: Update Avatar tests to verify animations**

Add these tests to `tests/Unit/Components/AvatarTest.js`:

```javascript
it('applies idle animation correctly', () => {
  const wrapper = mount(Avatar, { props: { state: 'idle' } });
  const styles = window.getComputedStyle(wrapper.find('.avatar').element);
  // Animation property should be set
  expect(wrapper.find('.state-idle').exists()).toBe(true);
});

it('applies tired state with opacity and filter', () => {
  const wrapper = mount(Avatar, { props: { state: 'tired' } });
  expect(wrapper.find('.state-tired').exists()).toBe(true);
});

it('applies thinking animation correctly', () => {
  const wrapper = mount(Avatar, { props: { state: 'thinking' } });
  expect(wrapper.find('.state-thinking').exists()).toBe(true);
});

it('applies happy animation on correct state', () => {
  const wrapper = mount(Avatar, { props: { state: 'happy' } });
  expect(wrapper.find('.state-happy').exists()).toBe(true);
});
```

- [ ] **Step 3: Run all tests**

```bash
npm run test -- tests/Unit/Components/AvatarTest.js
```

Expected: All 10 tests pass

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Gamification/Avatar.vue tests/Unit/Components/AvatarTest.js
git commit -m "feat: add avatar animation states (idle, happy, tired, thinking)

Animation implementations:
- idle: gentle vertical float (3s ease-in-out infinite)
- happy: scale bounce with easing (0.6s)
- tired: reduced opacity (0.7) + grayscale filter
- thinking: side-to-side sway (2s ease-in-out infinite)

Also added drop-shadow filters based on size (sm/md/lg).
Unit tests verify animation classes are applied correctly."
```

---

## Task 5: Update avatarStore.js for Proper Hydration

**Files:**
- Modify: `resources/js/Stores/gamification/avatarStore.js`
- Reference: `docs/superpowers/specs/2026-04-18-avatar-isometric-design.md` section "Integration Points"

- [ ] **Step 1: Review current avatarStore.js**

Read the existing file to understand current structure:

```bash
cat resources/js/Stores/gamification/avatarStore.js
```

- [ ] **Step 2: Update avatarStore to ensure default cosmetics**

Update `resources/js/Stores/gamification/avatarStore.js` to set proper defaults:

```javascript
import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getDefaultCosmetic } from '@/Data/cosmetics.js';

export const useAvatarStore = defineStore('avatar', () => {
  const equipped = ref({
    color: getDefaultCosmetic('color'),
    outfit: getDefaultCosmetic('outfit'),
    accessories: [],
    pet: getDefaultCosmetic('pet'),
    background: getDefaultCosmetic('background'),
  });

  /**
   * Set a cosmetic slot value.
   * For 'accessories': appends up to max 2; if already 2, shifts oldest and pushes new.
   * For other slots: sets directly.
   */
  function setEquipped(slot, code) {
    if (slot === 'accessories') {
      if (equipped.value.accessories.length >= 2) {
        equipped.value.accessories.shift();
      }
      equipped.value.accessories.push(code);
    } else {
      equipped.value[slot] = code;
    }
  }

  /**
   * Hydrate equipped from server response.
   * Server format: { color: { code }, outfit: { code }, pet: { code }, background: { code }, accessories: [{ code }] }
   * Null/undefined safe: skips missing or null slots, uses defaults.
   */
  function hydrateFromEquipped(serverEquipped) {
    if (!serverEquipped) return;

    const simpleSlots = ['color', 'outfit', 'pet', 'background'];
    for (const slot of simpleSlots) {
      const entry = serverEquipped[slot];
      if (entry && entry.code != null) {
        equipped.value[slot] = entry.code;
      }
    }

    // Handle accessories array
    if (serverEquipped.accessories && Array.isArray(serverEquipped.accessories)) {
      equipped.value.accessories = serverEquipped.accessories
        .map((item) => item?.code)
        .filter((code) => code != null)
        .slice(0, 2); // Max 2 accessories
    }
  }

  return {
    equipped,
    setEquipped,
    hydrateFromEquipped,
  };
});
```

- [ ] **Step 3: Create test file for avatarStore**

Create `tests/Unit/Stores/AvatarStoreTest.js`:

```javascript
import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAvatarStore } from '@/Stores/gamification/avatarStore.js';

describe('useAvatarStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with default cosmetics', () => {
    const store = useAvatarStore();
    expect(store.equipped.color).toBe('purple');
    expect(store.equipped.outfit).toBe('student_robes');
    expect(store.equipped.pet).toBe('dragon_purple');
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

  it('hydrates from server response', () => {
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
});
```

- [ ] **Step 4: Run store tests**

```bash
npm run test -- tests/Unit/Stores/AvatarStoreTest.js
```

Expected: All 7 tests pass

- [ ] **Step 5: Commit**

```bash
git add resources/js/Stores/gamification/avatarStore.js tests/Unit/Stores/AvatarStoreTest.js
git commit -m "feat: update avatarStore with proper defaults and hydration

Updates:
- Use getDefaultCosmetic() for initial state
- Enhanced hydrateFromEquipped() to handle accessories array
- Null/undefined safe with fallback to defaults
- Proper array slicing (max 2 accessories)

Added unit tests covering:
- Default initialization
- Setting individual cosmetics
- Accessory appending and shifting
- Server hydration with all fields
- Null response handling"
```

---

## Task 6: Integrate Avatar into AvatarCustomizer

**Files:**
- Modify: `resources/js/Components/Gamification/AvatarCustomizer.vue`

- [ ] **Step 1: Update AvatarCustomizer to use new Avatar component**

In `resources/js/Components/Gamification/AvatarCustomizer.vue`, update the preview section:

Find this section (approximately lines 30-50):

```vue
<!-- Old preview -->
<div class="flex flex-col items-center justify-start gap-4">
  <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Preview</p>
  <div class="relative w-40 h-40">
    <div class="absolute inset-0 rounded-full opacity-30"
      style="background: conic-gradient(from 0deg, #667eea, #764ba2, #a855f7, #667eea);" />
    <div class="relative z-10 flex items-center justify-center h-full">
      <AvatarAnimated icon="🎓" state="idle" size="lg" />
    </div>
  </div>
```

Replace with:

```vue
<!-- New preview with Avatar component -->
<div class="flex flex-col items-center justify-start gap-4">
  <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Preview</p>
  <div class="relative w-52 h-52">
    <div class="absolute inset-0 rounded-full opacity-30"
      style="background: conic-gradient(from 0deg, #667eea, #764ba2, #a855f7, #667eea);" />
    <div class="relative z-10 flex items-center justify-center h-full">
      <Avatar :equipped="equipped" :state="previewState" size="lg" />
    </div>
  </div>
```

And update imports at the top:

```vue
<script setup>
// Remove: import AvatarAnimated from '@/Components/Progress/AvatarAnimated.vue';
// Add:
import Avatar from '@/Components/Gamification/Avatar.vue';
// ... rest of imports

// Add data/computed:
const previewState = ref('idle');
```

- [ ] **Step 2: Test AvatarCustomizer renders preview correctly**

In browser, navigate to avatar customizer page and verify:
- Avatar displays in preview (not emoji)
- Avatar changes color when you select different color
- Avatar outfit changes when selecting outfit
- Accessories show up on avatar

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Gamification/AvatarCustomizer.vue
git commit -m "feat: integrate new Avatar component into AvatarCustomizer

Replaces emoji avatar preview with new SVG Avatar component:
- Removed AvatarAnimated emoji import
- Added Avatar component import
- Updated preview container to use Avatar with equipped cosmetics
- Avatar dynamically updates as user customizes cosmetics"
```

---

## Task 7: Integrate Avatar into Progress Dashboard

**Files:**
- Modify: `resources/js/Pages/Progress/Index.vue`

- [ ] **Step 1: Find where avatar is currently rendered in Progress/Index**

```bash
grep -n "AvatarAnimated\|avatar\|Avatar" resources/js/Pages/Progress/Index.vue | head -20
```

- [ ] **Step 2: Update Progress/Index to use new Avatar**

Import Avatar:

```vue
<script setup>
import Avatar from '@/Components/Gamification/Avatar.vue';
import { useAvatarStore } from '@/Stores/gamification/avatarStore.js';
// ... other imports

const avatarStore = useAvatarStore();
</script>
```

Replace old avatar rendering with:

```vue
<template>
  <!-- In the profile/header section -->
  <div class="avatar-display">
    <Avatar
      :equipped="avatarStore.equipped"
      state="idle"
      size="lg"
    />
  </div>
</template>
```

- [ ] **Step 3: Test Progress page displays avatar correctly**

Navigate to Progress/Index page in browser:
- Avatar displays with user's equipped cosmetics
- Avatar is large and centered
- Avatar animates with idle state

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Progress/Index.vue
git commit -m "feat: display Avatar component in Progress dashboard

Integrates new Avatar component to show user profile avatar:
- Uses avatarStore.equipped for current cosmetics
- Size set to lg for prominent display
- State set to idle for default behavior
- Removed old emoji/AvatarAnimated rendering"
```

---

## Task 8: Add Avatar Integration Tests

**Files:**
- Create: `tests/Integration/AvatarIntegrationTest.js`

- [ ] **Step 1: Write integration tests**

Create `tests/Integration/AvatarIntegrationTest.js`:

```javascript
import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import Avatar from '@/Components/Gamification/Avatar.vue';
import { useAvatarStore } from '@/Stores/gamification/avatarStore.js';

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
    const wrapper = mount(Avatar, {
      props: { state: 'idle' },
    });

    expect(wrapper.find('.state-idle').exists()).toBe(true);

    await wrapper.setProps({ state: 'happy' });
    expect(wrapper.find('.state-happy').exists()).toBe(true);

    await wrapper.setProps({ state: 'tired' });
    expect(wrapper.find('.state-tired').exists()).toBe(true);

    await wrapper.setProps({ state: 'thinking' });
    expect(wrapper.find('.state-thinking').exists()).toBe(true);
  });

  it('renders correctly at all sizes', () => {
    const sizes = ['sm', 'md', 'lg'];

    sizes.forEach((size) => {
      const wrapper = mount(Avatar, { props: { size } });
      expect(wrapper.find(`.size-${size}`).exists()).toBe(true);
      expect(wrapper.find('svg').exists()).toBe(true);
    });
  });

  it('applies cosmetic definitions correctly', () => {
    const wrapper = mount(Avatar, {
      props: {
        equipped: {
          color: 'golden',
          outfit: 'wizard_robes',
          pet: 'phoenix_gold',
          accessories: ['crown'],
          background: 'starfield',
        },
      },
    });

    // Verify cosmetics are retrieved (component should have them)
    expect(wrapper.vm.colorStyle.name).toBe('Dorado');
    expect(wrapper.vm.outfitStyle.name).toBe('Robes Mágico');
    expect(wrapper.vm.petStyle.name).toBe('Fénix Dorado');
  });
});
```

- [ ] **Step 2: Run integration tests**

```bash
npm run test -- tests/Integration/AvatarIntegrationTest.js
```

Expected: All 5 tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/AvatarIntegrationTest.js
git commit -m "test: add Avatar integration tests

Integration tests covering:
- Avatar rendering with store-provided equipped cosmetics
- Avatar updates when store changes
- All four animation states render correctly
- Avatar renders correctly at all three sizes (sm/md/lg)
- Cosmetic definitions are applied correctly from COSMETIC_DEFINITIONS"
```

---

## Final: Run All Tests and Verify

- [ ] **Step 1: Run complete test suite**

```bash
npm run test
```

Expected: All unit and integration tests pass (17+ tests)

- [ ] **Step 2: Verify in browser**

Navigate through:
- Avatar Customizer page → Avatar displays and updates with cosmetics selection
- Progress Dashboard → Avatar displays with user's equipped cosmetics
- All three sizes render crisp and clean

- [ ] **Step 3: Final commit**

```bash
git add -A
git commit -m "refactor: complete avatar system implementation

Complete isometric SVG avatar system with:
- Dynamic parametrized Avatar.vue component
- Centralized cosmetics.js definitions
- Four animation states (idle, happy, tired, thinking)
- Three responsive sizes (sm/md/lg)
- Full integration with avatarStore and cosmetics system
- 17+ unit and integration tests
- Updated AvatarCustomizer and Progress pages to use new Avatar

All tests passing, visual verification complete."
```
