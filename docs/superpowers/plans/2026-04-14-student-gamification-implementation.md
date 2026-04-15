# Student Gamification Implementation Plan
## Progresión Visual Clara + Avatar Companion

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor student pages (Dashboard, Quiz, Simulator, Progress) with reusable progress visualization components + interactive avatar companion to resolve confusion, boost engagement, and provide immediate feedback.

**Architecture:** Hybrid approach—modular Vue components for progress UI (ProgressBar, ProgressRadial, ProgressJourney, StatCard) + coordinated composables (useGameProgress, useProgressAnimation, useRewardFeedback) to centralize animation logic and state coordination. Avatar components (AvatarCompanion, AvatarTutor, AvatarShowcase, AvatarDialog) add emotional engagement. Motion.js powers all animations.

**Tech Stack:** Vue 3, Tailwind CSS 4, Motion.js 12.38, Inertia.js, Chart.js (existing)

**Phases:** 
- **Phase 1** (Week 1): Core progress components + Composables + Dashboard integration
- **Phase 2** (Week 2): Avatar components + Quiz/Simulator integration
- **Phase 3** (Week 3): Progress page + Audio feedback + Full testing + Refinement

---

## File Structure Overview

### New Files to Create

```
resources/js/
├── Components/Progress/
│   ├── ProgressBar.vue           (200 lines) — Animated horizontal progress bar
│   ├── ProgressRadial.vue        (250 lines) — Circular multi-ring progress
│   ├── ProgressJourney.vue       (280 lines) — RPG-style level roadmap
│   ├── StatCard.vue              (150 lines) — Subject progress card
│   ├── RewardFeedback.vue        (180 lines) — XP/reward overlay animation
│   ├── AvatarAnimated.vue        (200 lines) — Avatar with state animations
│   ├── AvatarShowcase.vue        (180 lines) — Large avatar display (250px)
│   ├── AvatarCompanion.vue       (250 lines) — Interactive clickable avatar
│   ├── AvatarTutor.vue           (280 lines) — Avatar as AI tutor (Quiz/Sim)
│   └── AvatarDialog.vue          (200 lines) — Dialog panel for avatar options
│
├── Composables/
│   ├── useGameProgress.js        (200 lines) — Data sync + progress calculations
│   ├── useProgressAnimation.js   (250 lines) — Motion.js animation triggers
│   └── useRewardFeedback.js      (180 lines) — Audio + contextual messages
│
├── Utils/
│   ├── avatarMessages.js         (150 lines) — Message pool + context logic
│   ├── animationConfig.js        (120 lines) — Motion.js easing/duration configs
│   └── progressCalculations.js   (100 lines) — XP/level/gap calculations
│
└── __tests__/
    ├── useGameProgress.spec.js
    ├── ProgressBar.spec.js
    ├── AvatarCompanion.spec.js
    └── avatarMessages.spec.js
```

### Files to Modify

- `resources/js/Pages/Dashboard.vue` — Integrate progress components + AvatarCompanion
- `resources/js/Pages/Quiz/Session.vue` — Add ProgressBar + AvatarTutor
- `resources/js/Pages/Simulator/Exam.vue` — Add ProgressBar + Prediction + AvatarTutor
- `resources/js/Pages/Progress/Index.vue` — Add AvatarShowcase + Radial + Timeline
- `resources/js/Layouts/AuthenticatedLayout.vue` — (Minor) — Optional theme toggles for avatar colors

---

## PHASE 1: Core Components + Composables + Dashboard (Week 1)

### Task 1: Create `useGameProgress.js` Composable

**Files:**
- Create: `resources/js/Composables/useGameProgress.js`
- Test: `resources/js/__tests__/useGameProgress.spec.js`

- [ ] **Step 1: Write failing tests**

```javascript
// resources/js/__tests__/useGameProgress.spec.js
import { describe, it, expect, beforeEach } from 'vitest';
import { useGameProgress } from '@/Composables/useGameProgress';
import { ref } from 'vue';

describe('useGameProgress', () => {
  let gameProgress;

  beforeEach(() => {
    const mockUser = ref({
      gamification: { current_level: 5, current_xp: 450, rank: 'Aprendiz', streak_days: 7 },
      gpa: 3.5,
    });
    const mockStats = ref({
      projection: { projected_score: 75, gap_to_goal: 15, goal_name: 'Ingeniería' },
      subject_mastery: [
        { name: 'Matemáticas', progress: 85, gap: 15 },
        { name: 'Física', progress: 60, gap: 40 },
      ],
    });
    gameProgress = useGameProgress(mockUser, mockStats);
  });

  it('should calculate progress percentage correctly', () => {
    expect(gameProgress.progressPercentage.value).toBe(75);
  });

  it('should determine gap status as PRÓXIMA when gap <= 10', () => {
    const closeGap = ref({
      projection: { gap_to_goal: 8 },
    });
    const pg = useGameProgress(ref({}), closeGap);
    expect(pg.gapStatus.value.text).toBe('META PRÓXIMA');
    expect(pg.gapStatus.value.color).toBe('text-orange-400');
  });

  it('should return journey stage based on level', () => {
    expect(gameProgress.journeyStage.value).toBe('Aprendiz');
  });

  it('should add XP and trigger level up when threshold reached', () => {
    gameProgress.addXP(200); // 450 + 200 = 650 > 500 (next level)
    expect(gameProgress.hasLeveledUp.value).toBe(true);
  });

  it('should get contextual avatar message based on streak', () => {
    const msg = gameProgress.getAvatarMessage('default');
    expect(msg).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
npm run test -- resources/js/__tests__/useGameProgress.spec.js
```

Expected output: `FAIL` — `useGameProgress is not defined`

- [ ] **Step 3: Write composable implementation**

```javascript
// resources/js/Composables/useGameProgress.js
import { computed, ref } from 'vue';

export function useGameProgress(userProps, statsProps) {
  // Reactive refs
  const currentXP = ref(userProps.value?.gamification?.current_xp ?? 0);
  const currentLevel = ref(userProps.value?.gamification?.current_level ?? 1);
  const streakDays = ref(userProps.value?.gamification?.streak_days ?? 0);
  const gpaActual = ref(userProps.value?.gpa ?? 0);
  const hasLeveledUp = ref(false);

  // Constants
  const XP_PER_LEVEL = 500;
  const LEVELS = ['Novato', 'Aprendiz', 'Adept', 'Experto', 'Maestro'];
  const LEVEL_THRESHOLDS = [0, 500, 1500, 3500, 7000];

  // Computed: Progress Percentage
  const progressPercentage = computed(() => {
    const gap = statsProps.value?.projection?.gap_to_goal ?? 0;
    const projected = statsProps.value?.projection?.projected_score ?? 0;
    if (gap <= 0) return 100;
    return Math.min(100, Math.round((projected / (projected + gap)) * 100));
  });

  // Computed: Gap Status
  const gapStatus = computed(() => {
    const gap = statsProps.value?.projection?.gap_to_goal;
    if (gap === null || gap === undefined) {
      return { text: 'NODO INACTIVO', color: 'text-gray-500', bg: 'bg-white/5' };
    }
    if (gap <= 0) {
      return { text: 'ZONA DE INGRESO', color: 'text-green-400', bg: 'bg-green-400/10' };
    }
    if (gap <= 10) {
      return { text: 'META PRÓXIMA', color: 'text-orange-400', bg: 'bg-orange-400/10' };
    }
    return { text: 'BRECHA CRÍTICA', color: 'text-red-400', bg: 'bg-red-400/10' };
  });

  // Computed: Journey Stage
  const journeyStage = computed(() => {
    const level = currentLevel.value;
    if (level < 2) return 'Novato';
    if (level < 4) return 'Aprendiz';
    if (level < 6) return 'Adept';
    if (level < 8) return 'Experto';
    return 'Maestro';
  });

  // Computed: Next Level XP Required
  const nextLevelXp = computed(() => {
    const nextThreshold = LEVEL_THRESHOLDS[currentLevel.value] ?? XP_PER_LEVEL * currentLevel.value;
    return Math.max(0, nextThreshold - currentXP.value);
  });

  // Method: Add XP
  const addXP = (amount) => {
    currentXP.value += amount;
    const nextThreshold = LEVEL_THRESHOLDS[currentLevel.value];
    if (currentXP.value >= nextThreshold) {
      currentLevel.value += 1;
      hasLeveledUp.value = true;
      setTimeout(() => { hasLeveledUp.value = false; }, 2000);
    }
  };

  // Method: Get Avatar Message
  const getAvatarMessage = (context) => {
    if (streakDays.value > 5) return '¡Vamos, estás en fuego! 🔥';
    if (streakDays.value === 0 && context === 'default') return 'Te echo de menos... 😢';
    if (gapStatus.value.text === 'META PRÓXIMA') return '¡Casi ahí! 💪';
    return '¡Tú puedes! 🚀';
  };

  return {
    currentXP,
    currentLevel,
    streakDays,
    progressPercentage,
    gapStatus,
    journeyStage,
    nextLevelXp,
    hasLeveledUp,
    addXP,
    getAvatarMessage,
  };
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
npm run test -- resources/js/__tests__/useGameProgress.spec.js
```

Expected: `PASS` — All tests green

- [ ] **Step 5: Commit**

```bash
git add resources/js/Composables/useGameProgress.js resources/js/__tests__/useGameProgress.spec.js
git commit -m "feat(composables): add useGameProgress for data coordination

- Calculate progress percentage, gap status, journey stage
- Track XP and level progression
- Generate contextual avatar messages
- Fully tested with unit tests"
```

---

### Task 2: Create `useProgressAnimation.js` Composable

**Files:**
- Create: `resources/js/Composables/useProgressAnimation.js`

- [ ] **Step 1: Write implementation**

```javascript
// resources/js/Composables/useProgressAnimation.js
import { animate } from 'motion';
import { ref } from 'vue';

export function useProgressAnimation() {
  const animationInProgress = ref(false);

  // Animation: Progress Bar Fill
  const animateProgressBar = (element, fromPercent, toPercent, duration = 0.8) => {
    if (!element) return;
    animate(
      element,
      { width: [`${fromPercent}%`, `${toPercent}%`] },
      { duration, easing: 'ease-out' }
    );
  };

  // Animation: Radial Arc Draw
  const animateRadialArc = (element, duration = 1.2) => {
    if (!element) return;
    animate(
      element,
      { pathLength: [0, 1] },
      { duration, easing: 'ease-in-out' }
    );
  };

  // Animation: Avatar Wave (click)
  const animateAvatarWave = (element, duration = 0.6) => {
    if (!element) return;
    animate(
      element,
      {
        rotate: [0, 10, -10, 0],
        scale: [1, 1.1, 1],
      },
      { duration, easing: 'ease-in-out' }
    );
  };

  // Animation: XP Floating
  const animateXpFloat = (element, duration = 0.8) => {
    if (!element) return;
    animate(
      element,
      {
        y: [0, -30],
        opacity: [1, 0],
      },
      { duration, easing: 'ease-out' }
    );
  };

  // Animation: Level Up Evolution
  const animateLevelUp = (element, duration = 1.5) => {
    if (!element) return;
    animate(
      element,
      {
        scale: [1, 1.3, 1],
        rotate: [0, 360, 0],
        filter: ['brightness(1)', 'brightness(1.5)', 'brightness(1)'],
      },
      { duration, type: 'spring', stiffness: 200, damping: 10 }
    );
  };

  // Animation: Journey Avatar Slide
  const animateJourneySlide = (element, fromX, toX, duration = 1.2) => {
    if (!element) return;
    animate(
      element,
      { x: [fromX, toX] },
      { duration, easing: 'ease-out' }
    );
  };

  // Animation: Streak Pulse
  const animateStreakPulse = (element) => {
    if (!element) return;
    animate(
      element,
      { scale: [1, 1.05, 1] },
      { duration: 0.5, repeat: Infinity, repeatDelay: 2 }
    );
  };

  return {
    animationInProgress,
    animateProgressBar,
    animateRadialArc,
    animateAvatarWave,
    animateXpFloat,
    animateLevelUp,
    animateJourneySlide,
    animateStreakPulse,
  };
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Composables/useProgressAnimation.js
git commit -m "feat(composables): add useProgressAnimation with motion.js triggers

- Progress bar fill animation
- Radial arc drawing
- Avatar wave/jump/level-up effects
- XP floating text
- Journey slide transitions
- Streak pulse effect"
```

---

### Task 3: Create `ProgressBar.vue` Component

**Files:**
- Create: `resources/js/Components/Progress/ProgressBar.vue`
- Test: `resources/js/__tests__/ProgressBar.spec.js`

- [ ] **Step 1: Write component**

```vue
<!-- resources/js/Components/Progress/ProgressBar.vue -->
<script setup>
import { ref, watch, onMounted } from 'vue';
import { useProgressAnimation } from '@/Composables/useProgressAnimation';

const props = defineProps({
  percentage: {
    type: Number,
    default: 0,
    validator: (val) => val >= 0 && val <= 100,
  },
  label: {
    type: String,
    default: null,
  },
  sublabel: {
    type: String,
    default: null,
  },
  height: {
    type: String,
    default: 'h-2',
  },
});

const progressFill = ref(null);
const previousPercentage = ref(0);
const { animateProgressBar } = useProgressAnimation();

onMounted(() => {
  previousPercentage.value = props.percentage;
});

watch(() => props.percentage, (newVal) => {
  animateProgressBar(progressFill.value, previousPercentage.value, newVal, 0.8);
  previousPercentage.value = newVal;
});
</script>

<template>
  <div class="space-y-2">
    <div v-if="label" class="flex justify-between items-center">
      <span class="text-sm font-semibold text-white">{{ label }}</span>
      <span class="text-xs text-gray-400">{{ percentage }}%</span>
    </div>

    <div class="w-full bg-white/10 rounded-full overflow-hidden" :class="height">
      <div
        ref="progressFill"
        class="h-full bg-gradient-to-r from-purple-500 to-blue-600 rounded-full transition-all"
        :style="{ width: `${percentage}%` }"
      />
    </div>

    <div v-if="sublabel" class="text-xs text-gray-500">
      {{ sublabel }}
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed — Tailwind handles it */
</style>
```

- [ ] **Step 2: Write tests**

```javascript
// resources/js/__tests__/ProgressBar.spec.js
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgressBar from '@/Components/Progress/ProgressBar.vue';

describe('ProgressBar.vue', () => {
  it('renders with correct percentage', () => {
    const wrapper = mount(ProgressBar, {
      props: { percentage: 50 },
    });
    const fill = wrapper.find('div[ref="progressFill"]');
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
    expect(() => {
      mount(ProgressBar, { props: { percentage: 150 } });
    }).toThrow();
  });
});
```

- [ ] **Step 3: Run tests**

```bash
npm run test -- resources/js/__tests__/ProgressBar.spec.js
```

Expected: `PASS`

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Progress/ProgressBar.vue resources/js/__tests__/ProgressBar.spec.js
git commit -m "feat(components): add ProgressBar with smooth animations

- Horizontal progress bar with gradient
- Animated fill using motion.js
- Optional label, percentage, sublabel
- Fully tested"
```

---

### Task 4: Create `ProgressJourney.vue` Component

**Files:**
- Create: `resources/js/Components/Progress/ProgressJourney.vue`

- [ ] **Step 1: Write component**

```vue
<!-- resources/js/Components/Progress/ProgressJourney.vue -->
<script setup>
import { computed, ref, watch } from 'vue';
import { useProgressAnimation } from '@/Composables/useProgressAnimation';

const props = defineProps({
  currentLevel: {
    type: Number,
    required: true,
  },
  currentStage: {
    type: String,
    required: true, // 'Novato', 'Aprendiz', 'Adept', 'Experto', 'Maestro'
  },
  levelsToNextStage: {
    type: Number,
    default: 0,
  },
});

const { animateLevelUp } = useProgressAnimation();
const avatar = ref(null);
const stages = ['Novato', 'Aprendiz', 'Adept', 'Experto', 'Maestro'];
const stageIndex = computed(() => stages.indexOf(props.currentStage));

watch(() => props.currentLevel, () => {
  // Trigger animation on level change
  if (avatar.value) {
    animateLevelUp(avatar.value, 1.5);
  }
});
</script>

<template>
  <div class="space-y-4">
    <!-- Stage Labels -->
    <div class="flex justify-between text-xs font-bold uppercase tracking-wider">
      <span v-for="(stage, idx) in stages" :key="stage"
        :class="{
          'text-orange-400': idx === stageIndex,
          'text-gray-600': idx !== stageIndex,
        }">
        {{ stage }}
      </span>
    </div>

    <!-- Journey Line with Progress -->
    <div class="relative h-12 bg-white/5 rounded-full overflow-hidden flex items-center px-4">
      <!-- Background Progress -->
      <div class="absolute left-0 top-0 h-full bg-gradient-to-r from-purple-500/20 to-blue-600/20"
        :style="{ width: `${(stageIndex / (stages.length - 1)) * 100}%` }" />

      <!-- Stage Markers -->
      <div class="relative w-full flex justify-between items-center">
        <div v-for="(stage, idx) in stages" :key="stage"
          class="relative flex flex-col items-center">
          <!-- Stage Circle -->
          <div :class="{
            'w-6 h-6 bg-orange-500 shadow-lg shadow-orange-500/50': idx === stageIndex,
            'w-4 h-4 bg-white/20': idx !== stageIndex,
          }"
            class="rounded-full transition-all z-10" />
        </div>
      </div>

      <!-- Avatar Indicator -->
      <div v-if="stageIndex !== -1" class="absolute top-1/2 -translate-y-1/2 z-20"
        :style="{ left: `${(stageIndex / (stages.length - 1)) * 100}%`, transform: 'translateX(-50%) translateY(-50%)' }">
        <div ref="avatar"
          class="w-10 h-10 bg-gradient-to-br from-purple-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold shadow-xl">
          ⭐
        </div>
      </div>
    </div>

    <!-- Distance to Next Stage -->
    <div v-if="levelsToNextStage > 0" class="text-center text-sm text-gray-400">
      Faltan <span class="text-orange-400 font-bold">{{ levelsToNextStage }}</span> niveles para siguiente etapa
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed */
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Progress/ProgressJourney.vue
git commit -m "feat(components): add ProgressJourney RPG-style roadmap

- Five stages: Novato → Aprendiz → Adept → Experto → Maestro
- Animated star indicator shows current position
- Distance to next stage displayed
- Level up animation triggers"
```

---

### Task 5: Create `StatCard.vue` Component

**Files:**
- Create: `resources/js/Components/Progress/StatCard.vue`

- [ ] **Step 1: Write component**

```vue
<!-- resources/js/Components/Progress/StatCard.vue -->
<script setup>
import { computed } from 'vue';
import ProgressBar from './ProgressBar.vue';

const props = defineProps({
  name: {
    type: String,
    required: true, // e.g., "Matemáticas"
  },
  progress: {
    type: Number,
    required: true, // 0-100
    validator: (val) => val >= 0 && val <= 100,
  },
  gap: {
    type: Number,
    default: 0, // Points remaining
  },
  trend: {
    type: String,
    enum: ['up', 'down', 'stable'],
    default: 'stable',
  },
});

const borderColor = computed(() => {
  if (props.progress >= 75) return 'border-green-500/50';
  if (props.progress >= 50) return 'border-yellow-500/50';
  return 'border-red-500/50';
});

const trendIcon = computed(() => {
  if (props.trend === 'up') return '↑';
  if (props.trend === 'down') return '↓';
  return '→';
});

const trendColor = computed(() => {
  if (props.trend === 'up') return 'text-green-400';
  if (props.trend === 'down') return 'text-red-400';
  return 'text-gray-400';
});
</script>

<template>
  <div :class="borderColor"
    class="rounded-2xl border border-white/10 bg-white/3 p-4 backdrop-blur hover:shadow-lg hover:shadow-orange-500/10 transition-all hover:scale-105 cursor-pointer space-y-3">
    <!-- Header: Name + Trend -->
    <div class="flex justify-between items-center">
      <h3 class="font-semibold text-white">{{ name }}</h3>
      <span :class="trendColor" class="text-lg font-bold">{{ trendIcon }}</span>
    </div>

    <!-- Progress Bar -->
    <ProgressBar :percentage="progress" :sublabel="`${gap} puntos restantes`" />

    <!-- Footer: Percentage -->
    <div class="text-right text-xs font-bold text-orange-400">
      {{ progress }}%
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed */
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Progress/StatCard.vue
git commit -m "feat(components): add StatCard for subject progress

- Subject name with trend indicator (up/down/stable)
- Progress bar with remaining points
- Color border based on strength (green/yellow/red)
- Hover effects (scale, shadow)"
```

---

### Task 6: Create `AvatarAnimated.vue` Base Component

**Files:**
- Create: `resources/js/Components/Progress/AvatarAnimated.vue`

- [ ] **Step 1: Write component**

```vue
<!-- resources/js/Components/Progress/AvatarAnimated.vue -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
  icon: {
    type: String,
    default: '👤', // Fallback avatar icon
  },
  state: {
    type: String,
    enum: ['idle', 'happy', 'tired', 'thinking'],
    default: 'idle',
  },
  size: {
    type: String,
    enum: ['sm', 'md', 'lg', 'xl'],
    default: 'md',
  },
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'w-12 h-12 text-2xl',
    md: 'w-20 h-20 text-4xl',
    lg: 'w-32 h-32 text-6xl',
    xl: 'w-64 h-64 text-8xl',
  };
  return sizes[props.size];
});

const stateClasses = computed(() => {
  const states = {
    idle: 'animate-pulse',
    happy: 'animate-bounce',
    tired: 'opacity-60',
    thinking: 'animate-spin',
  };
  return states[props.state] || '';
});

const glowClasses = computed(() => {
  const glows = {
    idle: '',
    happy: 'shadow-lg shadow-green-400/50',
    tired: 'shadow-lg shadow-gray-500/50',
    thinking: 'shadow-lg shadow-blue-400/50',
  };
  return glows[props.state] || '';
});
</script>

<template>
  <div :class="[sizeClasses, glowClasses, stateClasses]"
    class="rounded-full bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center transition-all duration-300">
    {{ icon }}
  </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 0.6s infinite;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Progress/AvatarAnimated.vue
git commit -m "feat(components): add AvatarAnimated base component

- Configurable size (sm/md/lg/xl)
- State-based animations (idle/happy/tired/thinking)
- Gradient background with glow effects
- Used by AvatarCompanion and AvatarTutor"
```

---

### Task 7: Create `AvatarCompanion.vue` Interactive Component

**Files:**
- Create: `resources/js/Components/Progress/AvatarCompanion.vue`
- Test: `resources/js/__tests__/AvatarCompanion.spec.js`

- [ ] **Step 1: Write component**

```vue
<!-- resources/js/Components/Progress/AvatarCompanion.vue -->
<script setup>
import { ref, computed } from 'vue';
import { useProgressAnimation } from '@/Composables/useProgressAnimation';
import AvatarAnimated from './AvatarAnimated.vue';

const props = defineProps({
  icon: {
    type: String,
    default: '👤',
  },
  streak: {
    type: Number,
    default: 0,
  },
  gap: {
    type: Number,
    default: 0,
  },
  context: {
    type: String,
    enum: ['dashboard', 'quiz', 'simulator', 'default'],
    default: 'default',
  },
});

const emit = defineEmits(['interaction']);

const { animateAvatarWave } = useProgressAnimation();
const avatar = ref(null);
const showMessage = ref(false);
const message = ref('');
const clickCount = ref(0);

const avatarState = computed(() => {
  if (clickCount.value > 3) return 'tired';
  return 'idle';
});

const messages = {
  dashboard: [
    '¡Vamos, estás en fuego! 🔥',
    'Te echo de menos... 😢',
    '¡Casi ahí! 💪',
    'Tú puedes 🚀',
  ],
  quiz: [
    'Confía en ti 💪',
    '¡Excelente! 🎉',
    'Eres un crack 🌟',
  ],
  default: [
    '¿Qué tal el día?',
    'Estoy aquí para ayudarte',
    'Vamos a aprender juntos',
  ],
};

const handleClick = () => {
  clickCount.value++;
  if (avatar.value) {
    animateAvatarWave(avatar.value, 0.6);
  }

  const contextMessages = messages[props.context] || messages.default;
  message.value = contextMessages[Math.floor(Math.random() * contextMessages.length)];
  showMessage.value = true;

  setTimeout(() => {
    showMessage.value = false;
  }, 3000);

  emit('interaction', { clickCount: clickCount.value, message: message.value });
};
</script>

<template>
  <div class="relative flex flex-col items-center space-y-3">
    <!-- Avatar -->
    <div ref="avatar" class="cursor-pointer" @click="handleClick">
      <AvatarAnimated :icon="icon" :state="avatarState" size="md" />
    </div>

    <!-- Message Bubble -->
    <Transition name="fade">
      <div v-if="showMessage"
        class="bg-gradient-to-br from-purple-500/20 to-blue-600/20 border border-purple-400/30 rounded-2xl px-4 py-2 text-sm text-white text-center backdrop-blur whitespace-nowrap">
        {{ message }}
      </div>
    </Transition>

    <!-- Click Hint -->
    <div class="text-xs text-gray-500">Clickea para hablar</div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
```

- [ ] **Step 2: Write tests**

```javascript
// resources/js/__tests__/AvatarCompanion.spec.js
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AvatarCompanion from '@/Components/Progress/AvatarCompanion.vue';

describe('AvatarCompanion.vue', () => {
  it('renders avatar with default icon', () => {
    const wrapper = mount(AvatarCompanion, {
      props: { icon: '👤' },
      global: {
        components: {
          AvatarAnimated: { template: '<div>Avatar</div>' },
        },
      },
    });
    expect(wrapper.find('[ref="avatar"]').exists()).toBe(true);
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
    await wrapper.find('[ref="avatar"]').trigger('click');
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
    await wrapper.find('[ref="avatar"]').trigger('click');
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
    const avatar = wrapper.find('[ref="avatar"]');
    for (let i = 0; i < 5; i++) {
      await avatar.trigger('click');
    }
    expect(wrapper.vm.avatarState).toBe('tired');
  });
});
```

- [ ] **Step 3: Run tests**

```bash
npm run test -- resources/js/__tests__/AvatarCompanion.spec.js
```

Expected: `PASS`

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Progress/AvatarCompanion.vue resources/js/__tests__/AvatarCompanion.spec.js
git commit -m "feat(components): add AvatarCompanion interactive avatar

- Click to trigger wave animation
- Context-aware messages (dashboard/quiz/default)
- State changes to tired after many clicks
- Emits interaction events for tracking
- Fully tested"
```

---

### Task 8: Update `Dashboard.vue` - Integrate Components

**Files:**
- Modify: `resources/js/Pages/Dashboard.vue` (lines 1-150)

- [ ] **Step 1: Add imports**

```vue
<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { animate, spring, stagger } from 'motion';
import { playSound } from '@/Utils/SoundService';
import { useTheme } from '@/Composables/useTheme';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// NEW IMPORTS
import ProgressBar from '@/Components/Progress/ProgressBar.vue';
import ProgressJourney from '@/Components/Progress/ProgressJourney.vue';
import StatCard from '@/Components/Progress/StatCard.vue';
import AvatarCompanion from '@/Components/Progress/AvatarCompanion.vue';
import { useGameProgress } from '@/Composables/useGameProgress';
import { useProgressAnimation } from '@/Composables/useProgressAnimation';
```

- [ ] **Step 2: Add composables and computed properties**

```vue
<script setup>
// ... existing code ...

// NEW: Game Progress Composable
const gameProgress = useGameProgress(
  ref({
    gamification: props.auth?.gamification || {},
    gpa: props.user_gpa,
  }),
  ref({
    projection: props.stats?.projection || {},
    subject_mastery: props.stats?.subject_mastery || [],
  })
);

const { animateLevelUp } = useProgressAnimation();

// Computed for subject cards
const subjectCards = computed(() => {
  return (props.stats?.subject_mastery || []).map((subject) => ({
    name: subject.name,
    progress: subject.progress,
    gap: subject.gap || 0,
    trend: subject.recent_change > 0 ? 'up' : subject.recent_change < 0 ? 'down' : 'stable',
  }));
});
</script>
```

- [ ] **Step 3: Update template to include new components**

```vue
<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      <!-- Header -->
      <section class="space-y-2">
        <h1 class="text-4xl font-black uppercase tracking-tight">¡Bienvenido, {{ page.props.auth.user.name }}!</h1>
        <p class="text-sm text-gray-400">Tu camino hacia la excelencia académica empieza aquí.</p>
      </section>

      <!-- Avatar Companion + Progress Radial (lado a lado) -->
      <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex justify-center md:col-span-1">
          <AvatarCompanion
            :icon="equippedCosmetics.avatar?.metadata?.icon_class || '👤'"
            :streak="gamification.streak_days"
            :gap="gameProgress.gapStatus.value?.text"
            context="dashboard"
            @interaction="(e) => console.log('Avatar interaction:', e)"
          />
        </div>

        <!-- Stats Cards (placeholder for ProgressRadial) -->
        <div class="md:col-span-2 space-y-4">
          <div class="rounded-2xl border border-cyan-500/30 bg-cyan-500/10 p-5">
            <p class="text-[11px] text-cyan-300 uppercase tracking-widest font-black">Nivel Actual</p>
            <p class="mt-3 text-4xl font-black text-white">{{ gamification.current_level }}</p>
            <p class="mt-2 text-xs text-gray-300">{{ gameProgress.journeyStage.value }}</p>
          </div>

          <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-5">
            <p class="text-[11px] text-emerald-300 uppercase tracking-widest font-black">XP Para Siguiente Nivel</p>
            <p class="mt-3 text-2xl font-black text-white">{{ gameProgress.nextLevelXp.value }}</p>
          </div>
        </div>
      </section>

      <!-- Progress Journey -->
      <section class="rounded-2xl border border-white/10 bg-white/3 p-6 space-y-4">
        <h2 class="text-lg font-black uppercase tracking-wider text-white">Tu Camino</h2>
        <ProgressJourney
          :currentLevel="gamification.current_level"
          :currentStage="gameProgress.journeyStage.value"
          :levelsToNextStage="Math.ceil(gameProgress.nextLevelXp.value / 500)"
        />
      </section>

      <!-- Subject Cards Grid -->
      <section class="space-y-4">
        <h2 class="text-lg font-black uppercase tracking-wider text-white">Progreso por Materia</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <StatCard
            v-for="subject in subjectCards"
            :key="subject.name"
            :name="subject.name"
            :progress="subject.progress"
            :gap="subject.gap"
            :trend="subject.trend"
          />
        </div>
      </section>

      <!-- Action Buttons -->
      <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Link href="/quiz"
          class="rounded-2xl border border-orange-500/30 bg-orange-500/10 hover:bg-orange-500/20 p-6 text-center transition-all">
          <p class="text-lg font-black text-orange-400">Iniciar Quiz</p>
          <p class="text-xs text-gray-400 mt-2">Practica preguntas aleatorias</p>
        </Link>

        <Link href="/simulator"
          class="rounded-2xl border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 p-6 text-center transition-all">
          <p class="text-lg font-black text-purple-400">Simulador</p>
          <p class="text-xs text-gray-400 mt-2">Examen completo cronometrado</p>
        </Link>
      </section>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Run dev server and test visually**

```bash
npm run dev
# Navigate to dashboard and verify components render
```

Expected: Dashboard shows avatar, progress journey, subject cards

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Dashboard.vue
git commit -m "feat(dashboard): integrate progress components and avatar companion

- Add ProgressBar, ProgressJourney, StatCard components
- Add AvatarCompanion for interaction
- Integrate useGameProgress composable
- Display subject mastery with trends
- New action buttons for Quiz/Simulator"
```

---

### Task 9: Create `RewardFeedback.vue` Component

**Files:**
- Create: `resources/js/Components/Progress/RewardFeedback.vue`

- [ ] **Step 1: Write component**

```vue
<!-- resources/js/Components/Progress/RewardFeedback.vue -->
<script setup>
import { ref, onMounted } from 'vue';
import { animate, stagger } from 'motion';

const props = defineProps({
  xp: {
    type: Number,
    default: 50,
  },
  show: {
    type: Boolean,
    default: true,
  },
  duration: {
    type: Number,
    default: 1500,
  },
});

const emit = defineEmits(['complete']);

const container = ref(null);
const xpText = ref(null);
const confetti = ref([]);

onMounted(() => {
  if (!props.show) return;

  // XP Float Animation
  animate(
    xpText.value,
    { y: [0, -50], opacity: [1, 0] },
    { duration: props.duration / 1000, easing: 'ease-out' }
  );

  // Create confetti
  const confettiCount = 20;
  for (let i = 0; i < confettiCount; i++) {
    const angle = (360 / confettiCount) * i;
    const velocity = 100 + Math.random() * 100;
    const x = Math.cos((angle * Math.PI) / 180) * velocity;
    const y = Math.sin((angle * Math.PI) / 180) * velocity;

    confetti.value.push({ id: i, x, y });
  }

  // Animate confetti
  animate(
    '.confetti',
    { y: [0, 200], opacity: [1, 0] },
    { duration: props.duration / 1000, easing: 'ease-out' }
  );

  // Complete callback
  setTimeout(() => {
    emit('complete');
  }, props.duration);
});
</script>

<template>
  <Transition name="reward">
    <div v-if="show" ref="container"
      class="fixed inset-0 pointer-events-none flex items-center justify-center">
      <!-- XP Text -->
      <div ref="xpText" class="text-6xl font-black text-yellow-400 drop-shadow-lg">
        +{{ xp }} XP
      </div>

      <!-- Confetti -->
      <div v-for="item in confetti" :key="item.id"
        class="confetti absolute w-2 h-2 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full"
        :style="{ left: '50%', top: '50%', transform: `translate(${item.x}px, ${item.y}px)` }" />

      <!-- Glow Effect -->
      <div class="absolute inset-0 rounded-full bg-gradient-to-r from-purple-500/30 to-blue-500/30 blur-3xl" />
    </div>
  </Transition>
</template>

<style scoped>
.reward-enter-active,
.reward-leave-active {
  transition: opacity 0.3s ease;
}

.reward-enter-from,
.reward-leave-to {
  opacity: 0;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Progress/RewardFeedback.vue
git commit -m "feat(components): add RewardFeedback with confetti animation

- XP floating text with fade out
- Confetti particles emanating from center
- Glow background pulse
- Customizable duration and XP amount"
```

---

### Task 10: Create Utility Files

**Files:**
- Create: `resources/js/Utils/avatarMessages.js`
- Create: `resources/js/Utils/animationConfig.js`
- Create: `resources/js/Utils/progressCalculations.js`

- [ ] **Step 1: Create avatarMessages.js**

```javascript
// resources/js/Utils/avatarMessages.js
export const avatarMessages = {
  dashboard: {
    motivation: [
      '¡Vamos, estás en fuego! 🔥',
      '¡Casi ahí! 💪',
      'Tú puedes 🚀',
      'Eres un crack 🌟',
      'Vamos a lograrlo juntos 🎯',
    ],
    concern: [
      'Te echo de menos... 😢',
      '¿Todo está bien?',
      'Vuelve cuando puedas',
    ],
    success: [
      '¡Excelente! 🎉',
      '¡Lo hiciste! 🥳',
      'Brillante 💫',
    ],
  },
  quiz: {
    motivation: [
      'Confía en ti 💪',
      '¡Vamos! 🚀',
      'Tú puedes 🌟',
    ],
    correct: [
      '¡Excelente! 🎉',
      'Correcto 👏',
      'Muy bien 🔥',
    ],
    incorrect: [
      'Casi lo tienes',
      'Intenta de nuevo 💪',
      'La próxima seguro',
    ],
  },
};

export function getContextualMessage(context, sentiment) {
  const messages = avatarMessages[context]?.[sentiment] || [];
  return messages[Math.floor(Math.random() * messages.length)] || 'Tú puedes 🚀';
}
```

- [ ] **Step 2: Create animationConfig.js**

```javascript
// resources/js/Utils/animationConfig.js
export const animationConfigs = {
  progressBar: {
    duration: 0.8,
    easing: 'ease-out',
  },
  radialArc: {
    duration: 1.2,
    easing: 'ease-in-out',
  },
  avatarWave: {
    duration: 0.6,
    easing: 'ease-in-out',
  },
  xpFloat: {
    duration: 0.8,
    easing: 'ease-out',
  },
  levelUp: {
    duration: 1.5,
    type: 'spring',
    stiffness: 200,
    damping: 10,
  },
  journeySlide: {
    duration: 1.2,
    easing: 'ease-out',
  },
  confetti: {
    duration: 1.5,
    stagger: 0.05,
  },
};
```

- [ ] **Step 3: Create progressCalculations.js**

```javascript
// resources/js/Utils/progressCalculations.js
export const LEVEL_THRESHOLDS = [0, 500, 1500, 3500, 7000];
export const XP_PER_LEVEL = 500;
export const JOURNEY_STAGES = ['Novato', 'Aprendiz', 'Adept', 'Experto', 'Maestro'];

export function calculateProgressPercentage(projected, gap) {
  if (gap <= 0) return 100;
  return Math.min(100, Math.round((projected / (projected + gap)) * 100));
}

export function getGapStatus(gap) {
  if (gap === null || gap === undefined) {
    return { text: 'NODO INACTIVO', color: 'text-gray-500', bg: 'bg-white/5' };
  }
  if (gap <= 0) {
    return { text: 'ZONA DE INGRESO', color: 'text-green-400', bg: 'bg-green-400/10' };
  }
  if (gap <= 10) {
    return { text: 'META PRÓXIMA', color: 'text-orange-400', bg: 'bg-orange-400/10' };
  }
  return { text: 'BRECHA CRÍTICA', color: 'text-red-400', bg: 'bg-red-400/10' };
}

export function getJourneyStage(level) {
  if (level < 2) return 'Novato';
  if (level < 4) return 'Aprendiz';
  if (level < 6) return 'Adept';
  if (level < 8) return 'Experto';
  return 'Maestro';
}

export function getNextLevelThreshold(currentLevel) {
  return LEVEL_THRESHOLDS[currentLevel] ?? XP_PER_LEVEL * currentLevel;
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Utils/avatarMessages.js resources/js/Utils/animationConfig.js resources/js/Utils/progressCalculations.js
git commit -m "feat(utils): add utility modules for messages, animations, and calculations

- avatarMessages: contextual message pools
- animationConfig: centralized animation configs
- progressCalculations: level thresholds and stage logic"
```

---

## PHASE 1 COMPLETE ✅

**Commits so far:** 10  
**Components created:** 6 (ProgressBar, ProgressJourney, StatCard, AvatarAnimated, AvatarCompanion, RewardFeedback)  
**Composables:** 2 (useGameProgress, useProgressAnimation)  
**Pages updated:** Dashboard  
**Tests written:** 4 (useGameProgress, ProgressBar, AvatarCompanion, avatarMessages)

**Next:** Phase 2 — Quiz/Simulator integration + AvatarTutor

---

## PHASE 2: Quiz/Simulator + AvatarTutor (Week 2)

*(Tasks 11-20 — Continue below)*

---

## ⏸️ PLAN CHECKPOINT

**Questions before continuing to Phase 2:**

1. ✅ Phase 1 tasks clear and doable?
2. ✅ Component boundaries make sense?
3. ✅ Testing approach appropriate?

**Status:** Ready for Phase 2 (Tasks 11-20) covering:
- AvatarTutor.vue
- AvatarDialog.vue
- Quiz/Session.vue integration
- Simulator/Exam.vue integration
- useRewardFeedback.js
- Unit & integration tests

**Ready to continue?** Or pause to review Phase 1?

---

## Next Steps After Plan Approval

**Execution Options:**

**1. Subagent-Driven (Recommended)**
- One task per fresh subagent
- Parallel execution where possible
- Full review between phases
- Use: `superpowers:subagent-driven-development`

**2. Inline Execution**
- Batch tasks in this session
- Checkpoints for review
- Use: `superpowers:executing-plans`

**Which approach would you prefer?**
