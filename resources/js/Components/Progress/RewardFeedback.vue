<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { animate } from 'motion';

const props = defineProps({
  xp: {
    type: Number,
    default: 50,
  },
  gold: {
    type: Number,
    default: 0,
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
const timeoutId = ref(null);

onMounted(async () => {
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

  // Wait for Vue to render confetti elements before animating
  await nextTick();

  // Animate confetti via container-scoped query (not global selector)
  const confettiElements = container.value?.querySelectorAll('.confetti-item');
  if (confettiElements?.length) {
    animate(
      confettiElements,
      { y: [0, 200], opacity: [1, 0] },
      { duration: props.duration / 1000, easing: 'ease-out' }
    );
  }

  // Complete callback
  timeoutId.value = setTimeout(() => {
    emit('complete');
  }, props.duration);
});

onUnmounted(() => {
  if (timeoutId.value) clearTimeout(timeoutId.value);
});
</script>

<template>
  <Transition name="reward">
    <div v-if="show" ref="container"
      class="fixed inset-0 pointer-events-none flex items-center justify-center">
      <!-- XP Text -->
      <div ref="xpText" class="flex flex-col items-center gap-2">
        <div class="text-6xl font-black text-yellow-400 drop-shadow-lg">+{{ xp }} XP</div>
        <div v-if="gold > 0" class="text-3xl font-black text-yellow-300 drop-shadow-lg">+{{ gold }} 🪙</div>
      </div>

      <!-- Confetti -->
      <div v-for="item in confetti" :key="item.id"
        class="confetti-item absolute w-2 h-2 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full"
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
