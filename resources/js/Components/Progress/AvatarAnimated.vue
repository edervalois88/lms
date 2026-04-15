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
