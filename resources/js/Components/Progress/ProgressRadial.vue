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

const RING_ANIMATION_DELAY_STEP = 0.2;

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
      { duration: animationConfigs.radialArc.duration, delay: RING_ANIMATION_DELAY_STEP, easing: animationConfigs.radialArc.easing }
    );
  }

  if (streakRingRef.value) {
    streakRingRef.value.style.strokeDasharray = c2;
    streakRingRef.value.style.strokeDashoffset = c2;
    animate(
      streakRingRef.value,
      { strokeDashoffset: c2 * (1 - streakPercent.value) },
      { duration: animationConfigs.radialArc.duration, delay: RING_ANIMATION_DELAY_STEP * 2, easing: animationConfigs.radialArc.easing }
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
        :transform="`rotate(-90 ${CX} ${CY})`"
      />

      <!-- Materias Ring (Medio) -->
      <circle
        ref="materiasRingRef"
        :cx="CX" :cy="CY" :r="RINGS[1].r"
        :stroke-width="RINGS[1].strokeWidth"
        stroke="#3b82f6"
        fill="none"
        stroke-linecap="round"
        :transform="`rotate(-90 ${CX} ${CY})`"
      />

      <!-- Streak Ring (Interior) -->
      <circle
        ref="streakRingRef"
        :cx="CX" :cy="CY" :r="RINGS[2].r"
        :stroke-width="RINGS[2].strokeWidth"
        stroke="#10b981"
        fill="none"
        stroke-linecap="round"
        :transform="`rotate(-90 ${CX} ${CY})`"
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
