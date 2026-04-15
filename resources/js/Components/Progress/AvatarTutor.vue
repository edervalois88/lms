<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { animate } from 'motion';

const props = defineProps({
  state: {
    type: String,
    enum: ['idle', 'explaining', 'thinking', 'celebrating', 'encouraging'],
    default: 'idle',
  },
  size: {
    type: String,
    enum: ['sm', 'md', 'lg', 'xl'],
    default: 'md',
  },
  visible: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['interaction']);

// Magic number constants
const MESSAGE_AUTO_HIDE_MS = 3000;
const ELLIPSIS_DELAY_BASE_MS = 100;

const avatarRef = ref(null);
const showMessage = ref(false);
const helpMessage = ref('');
const animationControls = ref([]);
const messageTimeoutId = ref(null);

const sizeClasses = {
  sm: 'w-16 h-16',
  md: 'w-24 h-24',
  lg: 'w-32 h-32',
  xl: 'w-40 h-40',
};

const stateGlows = {
  idle: 'ring-blue-500/30',
  explaining: 'ring-purple-500/30',
  thinking: 'ring-cyan-500/30',
  celebrating: 'ring-green-500/30',
  encouraging: 'ring-orange-500/30',
};

const helpMessages = {
  idle: 'Click for help 💡',
  explaining: 'I\'m explaining... ✋',
  thinking: 'Let me think... 🤔',
  celebrating: 'Great job! 🎉',
  encouraging: 'Keep going! 💪',
};

const sizeClass = computed(() => sizeClasses[props.size]);
const glowClass = computed(() => stateGlows[props.state]);

// Stop all animations
const stopAnimations = () => {
  animationControls.value.forEach(control => {
    if (control) {
      control.stop?.();
    }
  });
  animationControls.value = [];
};

// Setup state-specific animations
const setupAnimation = async () => {
  stopAnimations();

  if (!avatarRef.value) return;

  const element = avatarRef.value;

  switch (props.state) {
    case 'idle':
      // Subtle pulse animation
      animationControls.value.push(
        animate(element, { scale: [1, 1.05, 1] }, { duration: 2, repeat: Infinity })
      );
      break;

    case 'explaining':
      // Gesture motion - rotate with Y motion
      animationControls.value.push(
        animate(element, { rotate: [-5, 5, -5, 0] }, { duration: 1.5, repeat: Infinity }),
        animate(element, { y: [0, -5, 0] }, { duration: 1.5, repeat: Infinity })
      );
      break;

    case 'thinking':
      // Head scratch - rotate with scale
      animationControls.value.push(
        animate(element, { rotate: [-3, 3, -3, 0] }, { duration: 1.8, repeat: Infinity }),
        animate(element, { scale: [1, 1.05, 0.95, 1] }, { duration: 1.8, repeat: Infinity })
      );
      break;

    case 'celebrating':
      // Jump and spin
      animationControls.value.push(
        animate(element, { y: [0, -15, 0] }, { duration: 1.2, repeat: Infinity }),
        animate(element, { rotate: [0, 360, 0] }, { duration: 1.2, repeat: Infinity })
      );
      break;

    case 'encouraging':
      // Motivational bounce
      animationControls.value.push(
        animate(element, { y: [0, -8, 0] }, { duration: 1, repeat: Infinity }),
        animate(element, { scale: [1, 1.08, 1] }, { duration: 1, repeat: Infinity })
      );
      break;
  }
};

// Watch for state changes and update animation
watch(() => props.state, setupAnimation);

const handleClick = () => {
  helpMessage.value = helpMessages[props.state];
  showMessage.value = true;

  emit('interaction', {
    action: 'help',
    message: helpMessage.value,
  });

  // Clear any previous timeout
  if (messageTimeoutId.value) {
    clearTimeout(messageTimeoutId.value);
  }

  // Auto-hide message after MESSAGE_AUTO_HIDE_MS
  messageTimeoutId.value = setTimeout(() => {
    showMessage.value = false;
    messageTimeoutId.value = null;
  }, MESSAGE_AUTO_HIDE_MS);
};

onMounted(() => {
  setupAnimation();
});

onUnmounted(() => {
  stopAnimations();
  if (messageTimeoutId.value) {
    clearTimeout(messageTimeoutId.value);
  }
});
</script>

<template>
  <div v-if="visible" class="avatar-tutor-container">
    <!-- Avatar Circle with Glow -->
    <div
      ref="avatarRef"
      class="avatar-tutor cursor-pointer flex items-center justify-center rounded-full transition-all duration-300"
      :class="[sizeClass, `state-${state}`, `size-${size}`, 'bg-gradient-to-br from-indigo-600 to-purple-700 ring-4']"
      @click="handleClick"
    >
      <!-- Avatar Emoji -->
      <span class="text-4xl select-none">🎓</span>

      <!-- Thinking State - Animated Ellipsis -->
      <div v-if="state === 'thinking'" class="ellipsis-dots absolute -bottom-6 left-1/2 transform -translate-x-1/2 flex gap-1">
        <span class="w-1 h-1 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0s"></span>
        <span class="w-1 h-1 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0.2s"></span>
        <span class="w-1 h-1 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0.4s"></span>
      </div>
    </div>

    <!-- Help Message Bubble -->
    <Transition name="fade">
      <div
        v-if="showMessage"
        class="help-bubble mt-4 px-4 py-2 rounded-xl text-sm text-white text-center bg-gradient-to-br from-indigo-500/80 to-purple-600/80 backdrop-blur whitespace-nowrap"
      >
        {{ helpMessage }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.avatar-tutor-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.avatar-tutor {
  position: relative;
  will-change: transform;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* State-specific glow effects */
.state-idle {
  ring-color: rgba(59, 130, 246, 0.3);
}

.state-explaining {
  ring-color: rgba(168, 85, 247, 0.3);
}

.state-thinking {
  ring-color: rgba(34, 211, 238, 0.3);
}

.state-celebrating {
  ring-color: rgba(34, 197, 94, 0.3);
}

.state-encouraging {
  ring-color: rgba(249, 115, 22, 0.3);
}
</style>
