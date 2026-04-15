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
/* All styling via Tailwind */
</style>
