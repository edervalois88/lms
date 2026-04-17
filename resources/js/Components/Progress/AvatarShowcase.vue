<!-- resources/js/Components/Progress/AvatarShowcase.vue -->
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { animate } from 'motion';
import { animationConfigs } from '@/Utils/animationConfig';
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
      animationConfigs.rotatingBg
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

    <!-- Rewards Links -->
    <div class="flex gap-3">
      <a v-if="rewardsRoute" :href="rewardsRoute"
        class="px-5 py-2 bg-gradient-to-r from-purple-500 to-blue-600 text-white rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity">
        Tienda
      </a>
      <a href="/rewards/avatar"
        class="px-5 py-2 bg-white/10 border border-white/20 text-white rounded-xl font-semibold text-sm hover:bg-white/20 transition-colors">
        Personalizar
      </a>
    </div>
  </div>
</template>
