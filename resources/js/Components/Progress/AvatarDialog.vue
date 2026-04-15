<script setup>
import { ref, onUnmounted, watch, onMounted } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  context: {
    type: String,
    enum: ['quiz', 'dashboard', 'simulator'],
    default: 'quiz',
  },
});

const emit = defineEmits(['action', 'close']);

// Magic number constants
const AUTO_CLOSE_MS = 5000;

let autoCloseTimeoutId = null;

// Dialog options with emoji and labels
const dialogOptions = [
  {
    id: 'tip',
    emoji: '💡',
    label: 'Dame un tip',
    description: 'Sugerencia pedagógica',
  },
  {
    id: 'explain',
    emoji: '❓',
    label: 'Explícame esto',
    description: 'Llama tutor IA',
  },
  {
    id: 'roadmap',
    emoji: '🎯',
    label: '¿Qué sigue?',
    description: 'Roadmap de estudios',
  },
  {
    id: 'joke',
    emoji: '😂',
    label: 'Cuéntame un chiste',
    description: 'Mensaje motivador',
  },
];

const handleOptionClick = (optionId) => {
  emit('action', optionId);
  clearAutoCloseTimeout();
};

const handleOverlayClick = () => {
  emit('close');
  clearAutoCloseTimeout();
};

const setupAutoClose = () => {
  // Clear any existing timeout
  clearAutoCloseTimeout();

  // Set up new timeout for 5 seconds
  autoCloseTimeoutId = setTimeout(() => {
    emit('close');
    autoCloseTimeoutId = null;
  }, AUTO_CLOSE_MS);
};

const clearAutoCloseTimeout = () => {
  if (autoCloseTimeoutId) {
    clearTimeout(autoCloseTimeoutId);
    autoCloseTimeoutId = null;
  }
};

// Watch for open prop changes
watch(
  () => props.open,
  (newOpen) => {
    if (newOpen) {
      setupAutoClose();
    } else {
      clearAutoCloseTimeout();
    }
  },
  { immediate: true }
);

onMounted(() => {
  // Setup auto-close if dialog is already open on mount
  if (props.open) {
    setupAutoClose();
  }
});

onUnmounted(() => {
  clearAutoCloseTimeout();
});
</script>

<template>
  <Transition name="fade">
    <div
      v-if="open"
      class="avatar-dialog-overlay fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
      @click="handleOverlayClick"
    >
      <!-- Dialog Panel -->
      <Transition name="slideUp">
        <div
          v-if="open"
          class="avatar-dialog-panel bg-white/95 rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-white/20"
          @click.stop
        >
          <!-- Header -->
          <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-2">¿Cómo puedo ayudarte?</h2>
            <p class="text-sm text-gray-600">Selecciona una opción</p>
          </div>

          <!-- Options Grid (2 columns) -->
          <div class="grid grid-cols-2 gap-4 mb-6">
            <button
              v-for="option in dialogOptions"
              :key="option.id"
              class="dialog-option group relative flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-6 transition-all duration-200 hover:border-gray-300 hover:from-blue-50 hover:to-blue-100 hover:shadow-md active:scale-95"
              @click="handleOptionClick(option.id)"
            >
              <!-- Emoji -->
              <span class="text-3xl">{{ option.emoji }}</span>

              <!-- Label -->
              <span class="text-xs font-semibold text-gray-900 text-center leading-tight">
                {{ option.label }}
              </span>

              <!-- Description (hidden on smaller screens) -->
              <span class="hidden sm:block text-xs text-gray-500 text-center leading-tight">
                {{ option.description }}
              </span>
            </button>
          </div>

          <!-- Auto-close footer -->
          <div class="text-center text-xs text-gray-500 border-t border-gray-200 pt-4">
            Se cerrará automáticamente en 5s
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
/* Fade transition for overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide up transition for panel */
.slideUp-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slideUp-leave-active {
  transition: all 0.2s ease;
}

.slideUp-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.slideUp-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Dialog option hover effects */
.dialog-option {
  cursor: pointer;
}

.dialog-option:hover {
  transform: translateY(-2px);
}

.dialog-option:active {
  transform: translateY(0);
}
</style>
