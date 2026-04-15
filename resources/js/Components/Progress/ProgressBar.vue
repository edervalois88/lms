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

    <div data-testid="progress-container" class="w-full bg-white/10 rounded-full overflow-hidden" :class="height">
      <div
        ref="progressFill"
        data-testid="progress-fill"
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
/* Tailwind handles all styling */
</style>
