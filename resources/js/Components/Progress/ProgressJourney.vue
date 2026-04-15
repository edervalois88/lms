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
    animateLevelUp(avatar.value);
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
/* All styling done via Tailwind */
</style>
