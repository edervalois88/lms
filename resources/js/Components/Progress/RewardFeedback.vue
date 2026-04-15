<script setup>
import { ref, onMounted } from 'vue';
import { animate } from 'motion';

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
