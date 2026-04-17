<script setup>
import { watch, ref } from 'vue';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';
import { storeToRefs } from 'pinia';

const achievements = useAchievementsStore();
const { pendingToast } = storeToRefs(achievements);
const toastTimeout = ref(null);

watch(pendingToast, (val) => {
  if (toastTimeout.value) clearTimeout(toastTimeout.value);
  if (val) {
    toastTimeout.value = setTimeout(() => achievements.clearToast(), 5000);
  }
});
</script>

<template>
  <Transition name="achievement">
    <div v-if="pendingToast"
      class="fixed top-6 right-6 z-50 max-w-sm w-full bg-gray-900 border border-purple-500/50 rounded-2xl p-4 shadow-2xl shadow-purple-500/20">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-2xl">🏆</span>
        <span class="text-xs font-bold text-purple-400 uppercase tracking-widest">Logro desbloqueado</span>
      </div>
      <p class="text-white font-bold text-sm">{{ pendingToast.label }}</p>
      <div v-if="pendingToast.cosmeticUnlocked"
        class="mt-3 flex items-center gap-2 px-3 py-2 bg-purple-500/10 rounded-xl">
        <span class="text-xl">🎁</span>
        <p class="text-xs text-purple-300">¡Cosmética desbloqueada en tu inventario!</p>
      </div>
      <div class="mt-3 h-1 bg-white/10 rounded-full overflow-hidden">
        <div class="h-full bg-purple-500 rounded-full animate-shrink" />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.achievement-enter-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.achievement-leave-active { transition: all 0.3s ease-in; }
.achievement-enter-from, .achievement-leave-to { opacity: 0; transform: translateX(100%) scale(0.8); }
@keyframes shrink { from { width: 100%; } to { width: 0%; } }
.animate-shrink { animation: shrink 5s linear forwards; }
</style>
