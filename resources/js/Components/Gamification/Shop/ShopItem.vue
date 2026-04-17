<script setup>
import { computed } from 'vue';

const props = defineProps({
  item: { type: Object, required: true },
  canAfford: { type: Boolean, default: false },
});
const emit = defineEmits(['purchase']);

const rarityConfig = {
  common:   { label: 'Común',   color: 'text-gray-400',   border: 'border-gray-600' },
  uncommon: { label: 'Inusual', color: 'text-green-400',  border: 'border-green-600' },
  rare:     { label: 'Raro',    color: 'text-blue-400',   border: 'border-blue-600' },
  epic:     { label: 'Épico',   color: 'text-purple-400', border: 'border-purple-600' },
};
const rarity = computed(() => rarityConfig[props.item.rarity] ?? rarityConfig.common);
const statusLabel = computed(() => {
  if (props.item.equipped) return 'Equipado';
  if (props.item.owned) return 'En inventario';
  if (props.item.cost === 0 && props.item.unlockedBy) return 'Se desbloquea';
  return null;
});
</script>

<template>
  <div class="relative flex flex-col bg-white/5 border rounded-xl overflow-hidden transition-all duration-200"
    :class="[rarity.border, item.owned ? 'opacity-80' : 'hover:bg-white/10']">
    <div class="h-28 flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900 text-5xl">
      {{ item.metadata?.icon ?? '🎁' }}
    </div>
    <span class="absolute top-2 left-2 text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-black/50"
      :class="rarity.color">{{ rarity.label }}</span>
    <div class="p-3 flex flex-col gap-2 flex-1">
      <p class="text-sm font-semibold text-white leading-tight">{{ item.name }}</p>
      <div class="mt-auto">
        <span v-if="statusLabel" class="inline-block text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400 font-medium">
          {{ statusLabel }}
        </span>
        <button v-else :disabled="!canAfford" @click="emit('purchase', item)"
          class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors"
          :class="canAfford ? 'bg-yellow-500 hover:bg-yellow-400 text-black' : 'bg-gray-700 text-gray-500 cursor-not-allowed'">
          <span>🪙</span><span>{{ item.cost.toLocaleString() }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
