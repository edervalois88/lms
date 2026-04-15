<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useProgressAnimation } from '@/Composables/useProgressAnimation';
import { getContextualMessage } from '@/Utils/avatarMessages';
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
    enum: ['dashboard', 'quiz', 'simulator', 'progress', 'default'],
    default: 'default',
  },
});

const emit = defineEmits(['interaction']);

const { animateAvatarWave } = useProgressAnimation();
const avatar = ref(null);
const showMessage = ref(false);
const message = ref('');
const clickCount = ref(0);
const messageTimeoutId = ref(null);

const MESSAGE_AUTO_HIDE_MS = 3000;

const avatarState = computed(() => {
  if (clickCount.value > 3) return 'tired';
  return 'idle';
});

// Fallback messages for contexts not in avatarMessages
const fallbackMessages = [
  '¿Qué tal el día?',
  'Estoy aquí para ayudarte',
  'Vamos a aprender juntos',
];

const getMessage = () => {
  const knownContexts = ['dashboard', 'quiz', 'simulator', 'progress'];
  if (knownContexts.includes(props.context)) {
    return getContextualMessage(props.context, 'motivation');
  }
  return fallbackMessages[Math.floor(Math.random() * fallbackMessages.length)];
};

const handleClick = () => {
  clickCount.value++;
  if (avatar.value) {
    animateAvatarWave(avatar.value, 0.6);
  }

  message.value = getMessage();
  showMessage.value = true;

  if (messageTimeoutId.value) clearTimeout(messageTimeoutId.value);
  messageTimeoutId.value = setTimeout(() => {
    showMessage.value = false;
  }, MESSAGE_AUTO_HIDE_MS);

  emit('interaction', { clickCount: clickCount.value, message: message.value });
};

onUnmounted(() => {
  if (messageTimeoutId.value) clearTimeout(messageTimeoutId.value);
});
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
