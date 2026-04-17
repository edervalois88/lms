<script setup>
const props = defineProps({
  item: { type: Object, default: null },
  currentGold: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
  <Transition name="modal">
    <div v-if="item" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-1">Confirmar compra</h3>
        <p class="text-sm text-gray-400 mb-5">¿Quieres comprar este item?</p>
        <div class="flex items-center gap-4 p-4 bg-white/5 rounded-xl mb-5">
          <span class="text-4xl">{{ item.metadata?.icon ?? '🎁' }}</span>
          <div>
            <p class="font-semibold text-white">{{ item.name }}</p>
            <p class="text-xs text-gray-400 capitalize">{{ item.category }}</p>
          </div>
        </div>
        <div class="flex justify-between text-sm mb-2">
          <span class="text-gray-400">Tu Gold</span>
          <span class="text-yellow-400 font-bold">🪙 {{ currentGold.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between text-sm mb-2 pb-4 border-b border-white/10">
          <span class="text-gray-400">Costo</span>
          <span class="text-yellow-400 font-bold">🪙 {{ item.cost.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between text-sm mb-6">
          <span class="text-gray-400">Gold restante</span>
          <span class="font-bold" :class="(currentGold - item.cost) >= 0 ? 'text-white' : 'text-red-400'">
            🪙 {{ (currentGold - item.cost).toLocaleString() }}
          </span>
        </div>
        <div class="flex gap-3">
          <button @click="emit('cancel')" class="flex-1 py-2 rounded-xl border border-white/10 text-gray-400 hover:text-white transition-colors text-sm">Cancelar</button>
          <button @click="emit('confirm')" :disabled="loading"
            class="flex-1 py-2 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-black font-bold text-sm transition-colors disabled:opacity-50">
            {{ loading ? 'Comprando...' : 'Confirmar' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s, transform 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>
