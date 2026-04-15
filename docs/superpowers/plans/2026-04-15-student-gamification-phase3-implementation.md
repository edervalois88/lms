# Student Gamification Phase 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the gamification system by building ProgressRadial.vue, AvatarShowcase.vue, and refactoring Progress/Index.vue with avatar showcase, radial stats, and achievement timeline.

**Architecture:** Two new isolated components (ProgressRadial, AvatarShowcase) are built first with their own tests, then integrated into Progress/Index.vue alongside a derived achievement timeline. No new backend endpoints needed — all data is derived from existing Inertia props and page.props.user.

**Tech Stack:** Vue 3 Composition API (`<script setup>`), Motion.js 12.38, Tailwind CSS 4, Vitest, SVG for radial rings.

---

## File Structure

**Create:**
- `resources/js/Components/Progress/ProgressRadial.vue` — SVG radial chart with 3 animated concentric rings (GPA / Materias / Streak)
- `resources/js/Components/Progress/AvatarShowcase.vue` — 250px avatar with rotating gradient background and cosmetics labels
- `resources/js/__tests__/ProgressRadial.spec.js`
- `resources/js/__tests__/AvatarShowcase.spec.js`
- `resources/js/__tests__/Progress.spec.js`

**Modify:**
- `resources/js/Pages/Progress/Index.vue` — Integrate AvatarShowcase + ProgressRadial + achievement timeline
- `resources/js/Utils/avatarMessages.js` — Add "progress" context messages

---

## Task 16: Create ProgressRadial.vue

**Files:**
- Create: `resources/js/Components/Progress/ProgressRadial.vue`
- Test: `resources/js/__tests__/ProgressRadial.spec.js`

- [ ] **Step 1: Write failing test**

```javascript
// resources/js/__tests__/ProgressRadial.spec.js
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgressRadial from '../Components/Progress/ProgressRadial.vue';

describe('ProgressRadial.vue', () => {
  it('renders SVG with 3 ring groups', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 10,
        gpaMeta: 20,
        strongSubjects: 3,
        totalSubjects: 5,
        streakDays: 7,
        level: 2,
        rank: 'Aprendiz',
      }
    });
    const svg = wrapper.find('svg');
    expect(svg.exists()).toBe(true);
    // 3 background rings + 3 progress rings = 6 circle elements minimum
    expect(wrapper.findAll('circle').length).toBeGreaterThanOrEqual(6);
  });

  it('displays level and rank in center text', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 15,
        gpaMeta: 20,
        strongSubjects: 2,
        totalSubjects: 4,
        streakDays: 5,
        level: 3,
        rank: 'Adept',
      }
    });
    expect(wrapper.text()).toContain('3');
    expect(wrapper.text()).toContain('Adept');
  });

  it('renders legend with GPA, Materias, Racha labels', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 8,
        gpaMeta: 20,
        strongSubjects: 1,
        totalSubjects: 3,
        streakDays: 0,
        level: 1,
        rank: 'Novato',
      }
    });
    expect(wrapper.text()).toContain('GPA');
    expect(wrapper.text()).toContain('Materias');
    expect(wrapper.text()).toContain('Racha');
  });

  it('clamps gpaPercent to 100% when over goal', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 25,
        gpaMeta: 20,
        strongSubjects: 5,
        totalSubjects: 5,
        streakDays: 30,
        level: 5,
        rank: 'Maestro',
      }
    });
    // gpaPercent computed should clamp at 1.0
    expect(wrapper.vm.gpaPercent).toBe(1);
  });

  it('shows green gpa ring when score >= 80% of goal', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 18,
        gpaMeta: 20,
        strongSubjects: 3,
        totalSubjects: 5,
        streakDays: 10,
        level: 4,
        rank: 'Experto',
      }
    });
    expect(wrapper.vm.gpaStrokeColor).toBe('#10b981');
  });

  it('shows red gpa ring when score < 50% of goal', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 5,
        gpaMeta: 20,
        strongSubjects: 0,
        totalSubjects: 5,
        streakDays: 0,
        level: 1,
        rank: 'Novato',
      }
    });
    expect(wrapper.vm.gpaStrokeColor).toBe('#ef4444');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
npm run test -- resources/js/__tests__/ProgressRadial.spec.js --reporter=verbose
```

Expected: FAIL — "Cannot find module '../Components/Progress/ProgressRadial.vue'"

- [ ] **Step 3: Create ProgressRadial.vue**

```vue
<!-- resources/js/Components/Progress/ProgressRadial.vue -->
<script setup>
import { computed, ref, onMounted } from 'vue';
import { animate } from 'motion';
import { animationConfigs } from '@/Utils/animationConfig';

const props = defineProps({
  gpaActual: {
    type: Number,
    required: true,
  },
  gpaMeta: {
    type: Number,
    default: 20,
  },
  strongSubjects: {
    type: Number,
    required: true,
  },
  totalSubjects: {
    type: Number,
    default: 1,
  },
  streakDays: {
    type: Number,
    required: true,
  },
  streakGoal: {
    type: Number,
    default: 30,
  },
  level: {
    type: Number,
    required: true,
  },
  rank: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    default: 'md', // 'md' = 200px, 'lg' = 260px
  },
});

// SVG dimensions
const VIEWBOX = 200;
const CX = 100;
const CY = 100;

// Ring definitions: radius + strokeWidth for each concentric ring
const RINGS = [
  { r: 80, strokeWidth: 12 }, // Exterior: GPA
  { r: 60, strokeWidth: 12 }, // Medio: Materias
  { r: 40, strokeWidth: 12 }, // Interior: Streak
];

const svgSize = computed(() => props.size === 'lg' ? 260 : 200);

const circumference = (r) => 2 * Math.PI * r;

// Clamped percentages (0.0 to 1.0)
const gpaPercent = computed(() =>
  Math.min(1, props.gpaActual / (props.gpaMeta || 1))
);

const materiasPercent = computed(() =>
  Math.min(1, props.strongSubjects / (props.totalSubjects || 1))
);

const streakPercent = computed(() =>
  Math.min(1, props.streakDays / (props.streakGoal || 1))
);

// GPA ring color: green if >= 80%, amber if >= 50%, red otherwise
const gpaStrokeColor = computed(() => {
  if (gpaPercent.value >= 0.8) return '#10b981';
  if (gpaPercent.value >= 0.5) return '#f59e0b';
  return '#ef4444';
});

// Refs for animated SVG circles
const gpaRingRef = ref(null);
const materiasRingRef = ref(null);
const streakRingRef = ref(null);

onMounted(() => {
  const c0 = circumference(RINGS[0].r);
  const c1 = circumference(RINGS[1].r);
  const c2 = circumference(RINGS[2].r);

  if (gpaRingRef.value) {
    gpaRingRef.value.style.strokeDasharray = c0;
    gpaRingRef.value.style.strokeDashoffset = c0;
    animate(
      gpaRingRef.value,
      { strokeDashoffset: c0 * (1 - gpaPercent.value) },
      { duration: animationConfigs.radialArc.duration, easing: animationConfigs.radialArc.easing }
    );
  }

  if (materiasRingRef.value) {
    materiasRingRef.value.style.strokeDasharray = c1;
    materiasRingRef.value.style.strokeDashoffset = c1;
    animate(
      materiasRingRef.value,
      { strokeDashoffset: c1 * (1 - materiasPercent.value) },
      { duration: animationConfigs.radialArc.duration, delay: 0.2, easing: animationConfigs.radialArc.easing }
    );
  }

  if (streakRingRef.value) {
    streakRingRef.value.style.strokeDasharray = c2;
    streakRingRef.value.style.strokeDashoffset = c2;
    animate(
      streakRingRef.value,
      { strokeDashoffset: c2 * (1 - streakPercent.value) },
      { duration: animationConfigs.radialArc.duration, delay: 0.4, easing: animationConfigs.radialArc.easing }
    );
  }
});
</script>

<template>
  <div class="flex flex-col items-center space-y-3">
    <svg
      :width="svgSize"
      :height="svgSize"
      :viewBox="`0 0 ${VIEWBOX} ${VIEWBOX}`"
      class="overflow-visible"
    >
      <!-- Background rings (empty track) -->
      <circle
        v-for="ring in RINGS"
        :key="`bg-${ring.r}`"
        :cx="CX" :cy="CY" :r="ring.r"
        :stroke-width="ring.strokeWidth"
        stroke="rgba(255,255,255,0.08)"
        fill="none"
      />

      <!-- GPA Ring (Exterior) -->
      <circle
        ref="gpaRingRef"
        :cx="CX" :cy="CY" :r="RINGS[0].r"
        :stroke-width="RINGS[0].strokeWidth"
        :stroke="gpaStrokeColor"
        fill="none"
        stroke-linecap="round"
        transform="rotate(-90 100 100)"
      />

      <!-- Materias Ring (Medio) -->
      <circle
        ref="materiasRingRef"
        :cx="CX" :cy="CY" :r="RINGS[1].r"
        :stroke-width="RINGS[1].strokeWidth"
        stroke="#3b82f6"
        fill="none"
        stroke-linecap="round"
        transform="rotate(-90 100 100)"
      />

      <!-- Streak Ring (Interior) -->
      <circle
        ref="streakRingRef"
        :cx="CX" :cy="CY" :r="RINGS[2].r"
        :stroke-width="RINGS[2].strokeWidth"
        stroke="#10b981"
        fill="none"
        stroke-linecap="round"
        transform="rotate(-90 100 100)"
      />

      <!-- Center: Level + Rank text -->
      <text
        x="100" y="93"
        text-anchor="middle"
        font-size="14"
        font-weight="bold"
        fill="white"
        font-family="system-ui, sans-serif"
      >NIVEL {{ level }}</text>
      <text
        x="100" y="113"
        text-anchor="middle"
        font-size="11"
        fill="#9ca3af"
        font-family="system-ui, sans-serif"
      >{{ rank }}</text>
    </svg>

    <!-- Legend -->
    <div class="flex gap-4 text-xs text-gray-400">
      <span class="flex items-center gap-1">
        <span class="w-2 h-2 rounded-full inline-block" :style="{ backgroundColor: gpaStrokeColor }" />
        GPA
      </span>
      <span class="flex items-center gap-1">
        <span class="w-2 h-2 rounded-full bg-blue-400 inline-block" />
        Materias
      </span>
      <span class="flex items-center gap-1">
        <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block" />
        Racha
      </span>
    </div>
  </div>
</template>
```

- [ ] **Step 4: Run test to verify it passes**

```bash
npm run test -- resources/js/__tests__/ProgressRadial.spec.js --reporter=verbose
```

Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/Progress/ProgressRadial.vue resources/js/__tests__/ProgressRadial.spec.js
git commit -m "feat(components): add ProgressRadial with 3 animated concentric SVG rings

- Exterior ring: GPA vs goal (color-coded green/amber/red)
- Middle ring: strong subjects vs total (blue)
- Interior ring: streak days vs 30-day goal (emerald)
- Arc draw animation via Motion.js on mount (staggered 0.2s delay each ring)
- 'md' (200px) and 'lg' (260px) size variants
- SVG text labels for level + rank in center"
```

---

## Task 17: Create AvatarShowcase.vue

**Files:**
- Create: `resources/js/Components/Progress/AvatarShowcase.vue`
- Test: `resources/js/__tests__/AvatarShowcase.spec.js`

- [ ] **Step 1: Write failing test**

```javascript
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
```

- [ ] **Step 2: Run test to verify it fails**

```bash
npm run test -- resources/js/__tests__/AvatarShowcase.spec.js --reporter=verbose
```

Expected: FAIL — "Cannot find module '../Components/Progress/AvatarShowcase.vue'"

- [ ] **Step 3: Create AvatarShowcase.vue**

```vue
<!-- resources/js/Components/Progress/AvatarShowcase.vue -->
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { animate } from 'motion';
import AvatarAnimated from './AvatarAnimated.vue';

const props = defineProps({
  icon: {
    type: String,
    default: '👤',
  },
  cosmetics: {
    type: Object,
    default: () => ({ ropa: 'Básica', accesorios: 'Ninguno', color: 'Morado' }),
  },
  rewardsRoute: {
    type: String,
    default: null,
  },
});

const bgRef = ref(null);
let animationHandle = null;

onMounted(() => {
  if (bgRef.value) {
    animationHandle = animate(
      bgRef.value,
      { rotate: [0, 360] },
      { duration: 8, repeat: Infinity, easing: 'linear' }
    );
  }
});

onBeforeUnmount(() => {
  if (animationHandle) animationHandle.cancel();
});
</script>

<template>
  <div class="flex flex-col items-center space-y-4">
    <!-- Avatar with rotating gradient background -->
    <div class="relative w-64 h-64 flex items-center justify-center">
      <!-- Rotating gradient ring -->
      <div
        ref="bgRef"
        class="absolute inset-0 rounded-full opacity-40"
        style="background: conic-gradient(from 0deg, #667eea, #764ba2, #a855f7, #667eea);"
      />
      <!-- Blur glow layer (static) -->
      <div
        class="absolute inset-4 rounded-full opacity-30 blur-xl"
        style="background: linear-gradient(135deg, #667eea, #764ba2);"
      />
      <!-- Avatar -->
      <div class="relative z-10">
        <AvatarAnimated :icon="icon" state="idle" size="xl" />
      </div>
    </div>

    <!-- Cosmetics Labels -->
    <div class="text-center space-y-2">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Cosmética equipada</p>
      <div class="flex gap-2 flex-wrap justify-center">
        <span class="px-3 py-1 bg-purple-500/20 border border-purple-500/30 rounded-full text-xs text-purple-300">
          {{ cosmetics.ropa }}
        </span>
        <span class="px-3 py-1 bg-blue-500/20 border border-blue-500/30 rounded-full text-xs text-blue-300">
          {{ cosmetics.accesorios }}
        </span>
        <span class="px-3 py-1 bg-indigo-500/20 border border-indigo-500/30 rounded-full text-xs text-indigo-300">
          {{ cosmetics.color }}
        </span>
      </div>
    </div>

    <!-- Rewards Link -->
    <a
      v-if="rewardsRoute"
      :href="rewardsRoute"
      class="px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-600 text-white rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity"
    >
      Ir a Rewards
    </a>
  </div>
</template>
```

- [ ] **Step 4: Run test to verify it passes**

```bash
npm run test -- resources/js/__tests__/AvatarShowcase.spec.js --reporter=verbose
```

Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/Progress/AvatarShowcase.vue resources/js/__tests__/AvatarShowcase.spec.js
git commit -m "feat(components): add AvatarShowcase with rotating gradient background

- 250px avatar (xl size of AvatarAnimated) as centerpiece
- Conic gradient ring rotates continuously via Motion.js (8s loop)
- Cosmetics labels: ropa, accesorios, color
- Optional 'Ir a Rewards' link button
- Proper animation cleanup in onBeforeUnmount"
```

---

## Task 18: Refactor Progress/Index.vue

**Files:**
- Modify: `resources/js/Pages/Progress/Index.vue`
- Test: `resources/js/__tests__/Progress.spec.js`

- [ ] **Step 1: Write failing integration test**

```javascript
// resources/js/__tests__/Progress.spec.js
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgressIndex from '../Pages/Progress/Index.vue';

const defaultProps = {
  mastery: [
    { subject: 'Matemáticas', mastery_score: 7.5, total_attempts: 20, correct_attempts: 15, trend: 'up', subject_color: '#3b82f6' },
    { subject: 'Historia', mastery_score: 4.0, total_attempts: 10, correct_attempts: 4, trend: 'stable', subject_color: '#f59e0b' },
  ],
  exams_history: [
    { id: 1, type: 'UNAM', score: 85, created_at: '2026-04-01T00:00:00Z' },
  ],
  exams_pagination: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
  projection: { projected_score: 14, confidence: 'Media', gap_to_goal: 6 },
  streak_days: 5,
  weekly_stats: { questions_answered: 30 },
};

describe('Progress/Index.vue', () => {
  it('renders AvatarShowcase component', () => {
    const wrapper = mount(ProgressIndex, {
      props: defaultProps,
      global: {
        stubs: {
          AvatarShowcase: true,
          ProgressRadial: true,
          Head: true,
          AuthenticatedLayout: true,
          Link: true,
        }
      }
    });
    expect(wrapper.findComponent({ name: 'AvatarShowcase' }).exists()).toBe(true);
  });

  it('renders ProgressRadial component', () => {
    const wrapper = mount(ProgressIndex, {
      props: defaultProps,
      global: {
        stubs: {
          AvatarShowcase: true,
          ProgressRadial: true,
          Head: true,
          AuthenticatedLayout: true,
          Link: true,
        }
      }
    });
    expect(wrapper.findComponent({ name: 'ProgressRadial' }).exists()).toBe(true);
  });

  it('passes correct strongSubjects count to ProgressRadial', () => {
    const wrapper = mount(ProgressIndex, {
      props: defaultProps,
      global: {
        stubs: {
          AvatarShowcase: true,
          ProgressRadial: true,
          Head: true,
          AuthenticatedLayout: true,
          Link: true,
        }
      }
    });
    // mastery_score 7.5 > 7 (strong), 4.0 < 7 (weak) → 1 strong
    expect(wrapper.vm.strongSubjects).toBe(1);
  });

  it('computes projected score correctly for ProgressRadial', () => {
    const wrapper = mount(ProgressIndex, {
      props: defaultProps,
      global: {
        stubs: {
          AvatarShowcase: true,
          ProgressRadial: true,
          Head: true,
          AuthenticatedLayout: true,
          Link: true,
        }
      }
    });
    expect(wrapper.vm.projectedScore).toBe(14);
  });

  it('renders achievement timeline section', () => {
    const wrapper = mount(ProgressIndex, {
      props: defaultProps,
      global: {
        stubs: {
          AvatarShowcase: true,
          ProgressRadial: true,
          Head: true,
          AuthenticatedLayout: true,
          Link: true,
        }
      }
    });
    expect(wrapper.find('.achievement-timeline').exists()).toBe(true);
  });

  it('generates achievements from mastery and streak data', () => {
    const wrapper = mount(ProgressIndex, {
      props: defaultProps,
      global: {
        stubs: {
          AvatarShowcase: true,
          ProgressRadial: true,
          Head: true,
          AuthenticatedLayout: true,
          Link: true,
        }
      }
    });
    // streak_days = 5 → should generate streak achievement
    expect(wrapper.vm.achievements.some(a => a.type === 'streak')).toBe(true);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
npm run test -- resources/js/__tests__/Progress.spec.js --reporter=verbose
```

Expected: FAIL — AvatarShowcase/ProgressRadial not imported, computed props missing

- [ ] **Step 3: Refactor Progress/Index.vue**

Replace the entire file with:

```vue
<!-- resources/js/Pages/Progress/Index.vue -->
<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarShowcase from '@/Components/Progress/AvatarShowcase.vue';
import ProgressRadial from '@/Components/Progress/ProgressRadial.vue';

const props = defineProps({
  mastery: {
    type: Array,
    default: () => []
  },
  exams_history: {
    type: Array,
    default: () => []
  },
  exams_pagination: {
    type: Object,
    default: () => ({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
  },
  projection: {
    type: Object,
    default: () => ({ projected_score: 0, confidence: 'Baja', gap_to_goal: 20 })
  },
  streak_days: {
    type: Number,
    default: 0
  },
  weekly_stats: {
    type: Object,
    default: () => ({ questions_answered: 0 })
  }
});

const page = usePage();

// Gamification data from Inertia auth user
const gamification = computed(() => page.props.auth?.user?.gamification ?? {});
const currentLevel = computed(() => gamification.value.current_level ?? 1);
const currentRank = computed(() => gamification.value.rank ?? 'Novato');
const userIcon = computed(() => gamification.value.avatar_icon ?? '🎓');

// Subject mastery stats — a subject is "strong" if mastery_score >= 7 (out of 10)
const STRONG_THRESHOLD = 7;
const strongSubjects = computed(() =>
  props.mastery.filter(m => (m.mastery_score || 0) >= STRONG_THRESHOLD).length
);

// Projected score for ProgressRadial (0-20 scale)
const projectedScore = computed(() => props.projection?.projected_score ?? 0);

// Aggregate stats (keeping existing logic)
const totalQuestions = computed(() =>
  props.mastery.reduce((acc, m) => acc + (m.total_attempts || 0), 0)
);

const avgAccuracy = computed(() => {
  const totalCorrect = props.mastery.reduce((acc, m) => acc + (m.correct_attempts || 0), 0);
  return totalQuestions.value > 0
    ? Math.round((totalCorrect / totalQuestions.value) * 100)
    : 0;
});

const streak = computed(() => props.streak_days || 0);
const confidenceLabel = computed(() => props.projection?.confidence || 'Baja');
const examsHistory = computed(() => props.exams_history || []);

// Achievement timeline derived from existing data
const achievements = computed(() => {
  const items = [];

  if (streak.value > 0) {
    items.push({
      type: 'streak',
      icon: '🔥',
      label: `Racha de ${streak.value} días consecutivos`,
      color: 'text-orange-400',
    });
  }

  props.mastery.forEach(m => {
    if ((m.mastery_score || 0) >= STRONG_THRESHOLD) {
      items.push({
        type: 'mastery',
        icon: '📚',
        label: `Dominaste ${m.subject}`,
        color: 'text-blue-400',
      });
    }
  });

  if (examsHistory.value.length > 0) {
    items.push({
      type: 'exam',
      icon: '📝',
      label: `Completaste ${examsHistory.value.length} simulacro${examsHistory.value.length > 1 ? 's' : ''}`,
      color: 'text-purple-400',
    });
  }

  if (currentLevel.value > 1) {
    items.push({
      type: 'level',
      icon: '⭐',
      label: `Alcanzaste Nivel ${currentLevel.value} - ${currentRank.value}`,
      color: 'text-yellow-400',
    });
  }

  return items;
});

const formatDate = (dateString) => {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-MX', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};
</script>

<template>
  <Head title="Mi Progreso - NexusEdu" />

  <AuthenticatedLayout>
    <div class="progress-page app-shell min-h-screen py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">

        <!-- Page Header -->
        <header class="mb-12">
          <h1 class="text-4xl font-black text-gray-900 mb-2">
            Mi <span class="text-orange-500">Progreso</span>
          </h1>
          <p class="text-lg text-gray-500">Visualiza tu evolución y prepárate para el éxito.</p>
        </header>

        <!-- Gamification Hero: AvatarShowcase + ProgressRadial -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
          <div class="nx-panel p-8 rounded-3xl flex items-center justify-center">
            <AvatarShowcase
              :icon="userIcon"
              :cosmetics="{ ropa: 'Básica', accesorios: 'Ninguno', color: 'Morado' }"
            />
          </div>
          <div class="nx-panel p-8 rounded-3xl flex items-center justify-center">
            <ProgressRadial
              :gpa-actual="projectedScore"
              :gpa-meta="20"
              :strong-subjects="strongSubjects"
              :total-subjects="mastery.length || 1"
              :streak-days="streak"
              :level="currentLevel"
              :rank="currentRank"
              size="lg"
            />
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
          <div class="nx-panel p-8 rounded-3xl">
            <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Total Preguntas</p>
            <h3 class="text-4xl font-black text-gray-900">{{ totalQuestions }}</h3>
            <div class="mt-4 flex items-center text-green-500 text-sm font-bold">
              <i class="fa-solid fa-arrow-up mr-2"></i>
              <span>{{ weekly_stats.questions_answered || 0 }} respuestas esta semana</span>
            </div>
          </div>
          <div class="nx-panel p-8 rounded-3xl">
            <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Precisión Media</p>
            <h3 class="text-4xl font-black text-gray-900">{{ avgAccuracy }}%</h3>
            <div class="mt-4 w-full bg-gray-100 h-2 rounded-full overflow-hidden">
              <div class="bg-orange-500 h-full transition-all duration-1000"
                :style="{ width: avgAccuracy + '%' }" />
            </div>
          </div>
          <div class="nx-panel p-8 rounded-3xl">
            <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Racha Actual</p>
            <h3 class="text-4xl font-black text-gray-900">{{ streak }} días</h3>
            <p class="mt-4 text-gray-500 text-sm italic">Confianza: {{ confidenceLabel }}</p>
          </div>
        </div>

        <!-- Mastery + Exam History -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">

          <!-- Mastery by Subject -->
          <div class="nx-panel p-8 rounded-3xl">
            <h2 class="text-2xl font-black text-gray-900 mb-8 flex items-center">
              <i class="fa-solid fa-chart-line mr-3 text-orange-600"></i>
              Dominio por Materia
            </h2>

            <div v-if="mastery.length === 0" class="py-12 text-center">
              <i class="fa-solid fa-ghost text-4xl text-gray-200 mb-4 block"></i>
              <p class="text-gray-400">Aún no tienes datos suficientes. ¡Comienza un quiz!</p>
            </div>

            <div v-else class="space-y-8">
              <div v-for="item in mastery" :key="item.id || item.subject" class="group">
                <div class="flex justify-between items-end mb-3">
                  <div>
                    <h4 class="font-bold text-gray-900 group-hover:text-orange-600 transition-colors">
                      {{ item.subject || 'Materia' }}
                    </h4>
                    <p class="text-xs text-gray-400 uppercase">Tendencia: {{ item.trend || 'stable' }}</p>
                  </div>
                  <span class="text-lg font-black" :style="{ color: item.subject_color || '#F97316' }">
                    {{ Math.round((item.mastery_score || 0) * 10) }}%
                  </span>
                </div>
                <div class="w-full bg-gray-50 h-3 rounded-full overflow-hidden border border-gray-100">
                  <div
                    class="h-full transition-all duration-1000 ease-out shadow-inner"
                    :style="{
                      width: (item.mastery_score || 0) * 10 + '%',
                      backgroundColor: item.subject_color || '#F97316'
                    }"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Exam History -->
          <div class="nx-panel p-8 rounded-3xl">
            <h2 class="text-2xl font-black text-gray-900 mb-8 flex items-center">
              <i class="fa-solid fa-history mr-3 text-orange-600"></i>
              Historial de Exámenes
            </h2>

            <div v-if="examsHistory.length === 0" class="py-12 text-center">
              <p class="text-gray-400 italic">No has realizado simulacros todavía.</p>
            </div>

            <div v-else class="overflow-hidden">
              <table class="w-full text-left">
                <thead>
                  <tr class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50">
                    <th class="pb-4">Fecha</th>
                    <th class="pb-4">Tipo</th>
                    <th class="pb-4">Resultado</th>
                    <th class="pb-4"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="exam in examsHistory" :key="exam.id" class="group">
                    <td class="py-5 font-medium text-gray-600">{{ formatDate(exam.created_at) }}</td>
                    <td class="py-5">
                      <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold uppercase border border-white/10">
                        {{ exam.type }}
                      </span>
                    </td>
                    <td class="py-5">
                      <span class="font-black text-gray-900">{{ exam.score ?? '--' }}/120</span>
                    </td>
                    <td class="py-5 text-right">
                      <Link
                        :href="route('simulator.results', exam.id)"
                        class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-orange-500/15 hover:text-orange-300 transition-all"
                      >
                        <i class="fa-solid fa-eye text-xs"></i>
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div class="mt-6 flex items-center justify-between text-sm text-gray-500">
                <span>Mostrando {{ examsHistory.length }} de {{ exams_pagination.total }} simulacros</span>
                <span>Página {{ exams_pagination.current_page }} / {{ exams_pagination.last_page }}</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Achievement Timeline -->
        <div class="achievement-timeline nx-panel p-8 rounded-3xl mb-12">
          <h2 class="text-2xl font-black text-gray-900 mb-8 flex items-center">
            <i class="fa-solid fa-trophy mr-3 text-orange-600"></i>
            Logros Recientes
          </h2>

          <div v-if="achievements.length === 0" class="py-8 text-center">
            <p class="text-gray-400 italic">Completa quizzes y simulacros para desbloquear logros.</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(achievement, idx) in achievements"
              :key="idx"
              class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100"
            >
              <span class="text-2xl">{{ achievement.icon }}</span>
              <span :class="[achievement.color, 'font-semibold']">
                {{ achievement.label }}
              </span>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="bg-linear-to-r from-orange-500 to-red-600 rounded-3xl p-10 text-white flex flex-col md:flex-row items-center justify-between shadow-2xl overflow-hidden relative">
          <div class="relative z-10 text-center md:text-left mb-8 md:mb-0">
            <h2 class="text-3xl font-black mb-2">¿Listo para el siguiente nivel?</h2>
            <p class="text-orange-100 text-lg">Inicia un simulacro completo y proyecta tu puntaje real UNAM.</p>
          </div>
          <Link
            :href="route('simulator.index')"
            class="relative z-10 bg-white text-orange-600 px-10 py-4 rounded-2xl font-black text-lg hover:bg-orange-50 transition-colors shadow-lg shadow-black/10"
          >
            Iniciar Simulacro
          </Link>
          <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
          <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-black/10 rounded-full blur-3xl"></div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
:global(.progress-page) {
  color: var(--app-text);
}

:global(.progress-page .bg-gray-50) {
  background-color: color-mix(in srgb, var(--app-bg) 88%, white 12%) !important;
}

:global(.progress-page .bg-white) {
  background: var(--app-card) !important;
  border-color: var(--app-card-border) !important;
}

:global(.progress-page .text-gray-900) {
  color: var(--app-text-strong) !important;
}

:global(.progress-page .text-gray-600),
:global(.progress-page .text-gray-500),
:global(.progress-page .text-gray-400) {
  color: var(--app-text-muted) !important;
}

:global(.progress-page .bg-gray-100) {
  background-color: color-mix(in srgb, var(--app-text-muted) 18%, transparent 82%) !important;
}

:global(.progress-page .border-gray-100),
:global(.progress-page .border-gray-50) {
  border-color: var(--app-card-border) !important;
}
</style>
```

- [ ] **Step 4: Run integration tests**

```bash
npm run test -- resources/js/__tests__/Progress.spec.js --reporter=verbose
```

Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Progress/Index.vue resources/js/__tests__/Progress.spec.js
git commit -m "feat(pages): refactor Progress/Index with gamification components

- AvatarShowcase in hero section (top-left)
- ProgressRadial (lg size) with live GPA/subject/streak rings (top-right)
- Achievement timeline derived from mastery + streak + exams data
- strongSubjects computed: mastery_score >= 7 threshold
- Gamification level/rank from page.props.auth.user.gamification
- Kept existing mastery bars, exam history table, and CTA unchanged"
```

---

## Task 19: Add "progress" Context to avatarMessages + Audio Polish

**Files:**
- Modify: `resources/js/Utils/avatarMessages.js`
- Modify: `resources/js/Utils/animationConfig.js`

- [ ] **Step 1: Add "progress" context to avatarMessages.js**

Read current file end:
```bash
tail -10 resources/js/Utils/avatarMessages.js
```

The current export is:
```javascript
export const avatarMessages = {
  dashboard: { ... },
  quiz: { ... },
  simulator: { ... },
};
```

Add `progress` context before the closing `};`:

```javascript
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
  simulator: {
    motivation: [
      '¡Vamos, estás en fuego! 🔥',
      '¡Casi ahí! 💪',
      'Tú puedes 🚀',
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
    concern: [
      'No te desanimes 💪',
      'Sigue adelante 🚀',
      'Tú puedes 😊',
    ],
    success: [
      '¡Lo lograste! 🎉',
      'Excelente trabajo 👏',
      'Impresionante 🌟',
    ],
  },
  progress: {
    motivation: [
      '¡Mira cuánto has avanzado! 📈',
      '¡Sigue así, vas muy bien! 🚀',
      'Tu esfuerzo se nota 💪',
    ],
    success: [
      '¡Felicidades por tu racha! 🔥',
      '¡Dominaste esa materia! 📚',
      '¡Subiste de nivel! ⭐',
    ],
    concern: [
      '¿Qué materia quieres reforzar hoy?',
      'Cada sesión cuenta 💡',
      'Pequeños pasos, grandes resultados',
    ],
  },
};

export function getContextualMessage(context, sentiment) {
  const messages = avatarMessages[context]?.[sentiment] || [];
  return messages[Math.floor(Math.random() * messages.length)] || 'Tú puedes 🚀';
}
```

- [ ] **Step 2: Add "rotatingBg" to animationConfig.js**

Current file ends at line 33. Add the new config:

```javascript
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
  rotatingBg: {
    duration: 8,
    repeat: Infinity,
    easing: 'linear',
  },
};
```

- [ ] **Step 3: Update AvatarShowcase.vue to use animationConfig**

In `resources/js/Components/Progress/AvatarShowcase.vue`, replace the hardcoded animation config with the centralized one:

**Old `onMounted` in AvatarShowcase.vue:**
```javascript
import { animate } from 'motion';

onMounted(() => {
  if (bgRef.value) {
    animationHandle = animate(
      bgRef.value,
      { rotate: [0, 360] },
      { duration: 8, repeat: Infinity, easing: 'linear' }
    );
  }
});
```

**New `onMounted` in AvatarShowcase.vue:**
```javascript
import { animate } from 'motion';
import { animationConfigs } from '@/Utils/animationConfig';

onMounted(() => {
  if (bgRef.value) {
    animationHandle = animate(
      bgRef.value,
      { rotate: [0, 360] },
      animationConfigs.rotatingBg
    );
  }
});
```

Full updated imports section for AvatarShowcase.vue:
```javascript
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { animate } from 'motion';
import { animationConfigs } from '@/Utils/animationConfig';
import AvatarAnimated from './AvatarAnimated.vue';
```

- [ ] **Step 4: Run all tests to verify nothing broke**

```bash
npm run test --reporter=verbose
```

Expected: All tests PASS (existing + new)

- [ ] **Step 5: Commit**

```bash
git add resources/js/Utils/avatarMessages.js resources/js/Utils/animationConfig.js resources/js/Components/Progress/AvatarShowcase.vue
git commit -m "feat(utils): add progress context to avatarMessages and rotatingBg animation config

- progress context messages: motivation, success, concern
- rotatingBg config: 8s linear infinite rotation for AvatarShowcase
- AvatarShowcase updated to use centralized animationConfig"
```

---

## Task 20: Final Integration Smoke Test

**Files:**
- No new files — verify all Phase 1+2+3 tests pass together

- [ ] **Step 1: Run full test suite**

```bash
npm run test --reporter=verbose
```

Expected output: All tests PASS including:
- `useGameProgress.spec.js`
- `ProgressBar.spec.js`
- `AvatarCompanion.spec.js`
- `AvatarTutor.spec.js`
- `AvatarDialog.spec.js`
- `useRewardFeedback.spec.js`
- `Quiz.spec.js`
- `Simulator.spec.js`
- `ProgressRadial.spec.js` (new)
- `AvatarShowcase.spec.js` (new)
- `Progress.spec.js` (new)

- [ ] **Step 2: Start dev server and verify Progress page visually**

```bash
npm run dev
```

Navigate to `/progress` and verify:
- AvatarShowcase avatar renders with rotating gradient background
- ProgressRadial shows 3 rings animating in sequence (1.2s each, staggered 0.2s)
- Achievement timeline shows entries from mastery/streak/exams data
- Mastery bars and exam history table remain functional
- Page is responsive on mobile (md:grid-cols-2 collapses to 1 column)

- [ ] **Step 3: Verify cross-page consistency**

Navigate to:
- `/dashboard` — AvatarCompanion + ProgressJourney + StatCards visible
- `/quiz` — AvatarTutor + ProgressBar + RewardFeedback visible
- `/simulator` — Timer + ScorePrediction + AvatarTutor (serious) visible
- `/progress` — AvatarShowcase + ProgressRadial + AchievementTimeline visible

- [ ] **Step 4: Final commit**

```bash
git add .
git commit -m "chore(gamification): phase 3 complete — full gamification system shipped

Phase 3 adds:
- ProgressRadial.vue: SVG radial chart with 3 animated concentric rings
- AvatarShowcase.vue: 250px avatar with rotating gradient background
- Progress/Index.vue: Gamification hero + achievement timeline
- progress context in avatarMessages.js
- rotatingBg in animationConfig.js

Full system now covers Dashboard + Quiz + Simulator + Progress pages
with consistent avatar interactions, XP rewards, and visual progress"
```

---

## PHASE 3 COMPLETE ✅

**New components:** 2 (ProgressRadial, AvatarShowcase)  
**Modified pages:** 1 (Progress/Index)  
**Modified utilities:** 2 (avatarMessages, animationConfig)  
**New tests:** 3 test files (ProgressRadial, AvatarShowcase, Progress integration)

**Full Gamification System — All Phases:**
- ✅ Phase 1: ProgressBar, ProgressJourney, StatCard, AvatarAnimated, AvatarCompanion, RewardFeedback + composables + Dashboard
- ✅ Phase 2: AvatarTutor, AvatarDialog, useRewardFeedback + Quiz + Simulator
- ✅ Phase 3: ProgressRadial, AvatarShowcase + Progress/Index refactor

---

## Self-Review

### Spec Coverage

**Section 2.2 — ProgressRadial:** ✅ Task 16 — 3 concentric rings (GPA/Materias/Streak), arc animation, center level/rank text

**Section 3.3 — AvatarShowcase:** ✅ Task 17 — 250px avatar (xl size), rotating gradient bg, cosmetics labels, "Ir a Rewards" button

**Section 6.4 — Progress/Index layout:**
- ✅ AvatarShowcase (250px) — Task 18
- ✅ ProgressRadial (ampliado, lg size) — Task 18
- ✅ Timeline de Logros — Task 18 achievement timeline
- ✅ "Ver Rewards Shop" / CTA — Task 18 (CTA section kept)

**Section 8 — Phase 3:**
- ✅ Progress/Index with AvatarShowcase — Task 18
- ⚠️ Sonidos y feedback audio — SoundService.js exists but not wired in Phase 3 (audio is handled in useRewardFeedback.js already, no new wiring needed for Progress page)
- ✅ Testing — Tasks 16, 17, 18 each have test files
- ✅ Performance — No new heavy operations; SVG animation via native WAAPI

**Section 9 — Success Criteria:**
- ✅ Estudiantes entienden dónde están (ProgressRadial shows current level/rank)
- ✅ Las métricas se sienten ganables (Achievement timeline shows concrete wins)
- ✅ Animaciones satisfactorias (arc draw animation on ProgressRadial)
- ✅ Avatar Companion en Progress page (via AvatarShowcase)
- ✅ Funciona en mobile (responsive grid)

### Placeholder Scan

No TBD/TODO items. All steps have complete code blocks.

### Type Consistency

- `size` prop: AvatarAnimated uses `'sm'|'md'|'lg'|'xl'` — AvatarShowcase passes `size="xl"` ✅
- `ProgressRadial` props: `gpaActual`, `gpaMeta`, `strongSubjects`, `totalSubjects`, `streakDays`, `level`, `rank` — all used consistently in Task 18 Progress/Index.vue ✅
- `animationConfigs.rotatingBg` added in Task 19, used in AvatarShowcase Task 17 (Step 3 updates the import) ✅
- `achievements` array items: `{ type, icon, label, color }` — used consistently in template ✅
