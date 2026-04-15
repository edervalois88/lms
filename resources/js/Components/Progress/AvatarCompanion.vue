<script setup>
import { ref, computed } from 'vue';
import { useProgressAnimation } from '@/Composables/useProgressAnimation';
import AvatarAnimated from './AvatarAnimated.vue';

const props = defineProps({
  icon: {
    type: String,
    default: '👤',
  },
  streak: {
    type: Number,
    default: 0,
  },
  gap: {
    type: Number,
    default: 0,
  },
  context: {
    type: String,
    enum: ['dashboard', 'quiz', 'simulator', 'default'],
    default: 'default',
  },
});

const emit = defineEmits(['interaction']);

const { animateAvatarWave } = useProgressAnimation();
const avatar = ref(null);
const showMessage = ref(false);
const message = ref('');
const clickCount = ref(0);

const avatarState = computed(() => {
  if (clickCount.value > 3) return 'tired';
  return 'idle';
});

const messages = {
  dashboard: [
    '¡Vamos, estás en fuego! 🔥',
    'Te echo de menos... 😢',
    '¡Casi ahí! 💪',
    'Tú puedes 🚀',
  ],
  quiz: [
    'Confía en ti 💪',
    '¡Excelente! 🎉',
    'Eres un crack 🌟',
  ],
  default: [
    '¿Qué tal el día?',
    'Estoy aquí para ayudarte',
    'Vamos a aprender juntos',
  ],
};

const handleClick = () => {
  clickCount.value++;
  if (avatar.value) {
    animateAvatarWave(avatar.value, 0.6);
  }

  const contextMessages = messages[props.context] || messages.default;
  message.value = contextMessages[Math.floor(Math.random() * contextMessages.length)];
  showMessage.value = true;

  setTimeout(() => {
    showMessage.value = false;
  }, 3000);

  emit('interaction', { clickCount: clickCount.value, message: message.value });
};
</script>

<template>
  <div class="relative flex flex-col items-center space-y-3">
    <!-- Avatar -->
    <div ref="avatar" class="cursor-pointer avatar-click" @click="handleClick">
      <AvatarAnimated :icon="icon" :state="avatarState" size="md" />
    </div>

    <!-- Message Bubble -->
    <Transition name="fade">
      <div v-if="showMessage"
        class="bg-gradient-to-br from-purple-500/20 to-blue-600/20 border border-purple-400/30 rounded-2xl px-4 py-2 text-sm text-white text-center backdrop-blur whitespace-nowrap">
        {{ message }}
      </div>
    </Transition>

    <!-- Click Hint -->
    <div class="text-xs text-gray-500">Clickea para hablar</div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
