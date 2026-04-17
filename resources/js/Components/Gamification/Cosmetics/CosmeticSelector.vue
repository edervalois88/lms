<script setup>
import { computed } from 'vue';
import { useShopStore } from '@/Stores/gamification/shopStore';
import { useAvatarStore } from '@/Stores/gamification/avatarStore';
import { storeToRefs } from 'pinia';

const props = defineProps({
  slot: { type: String, required: true },
  label: { type: String, required: true },
});
const emit = defineEmits(['equip']);

const shop = useShopStore();
const avatar = useAvatarStore();
const { inventory } = storeToRefs(shop);
const { equipped } = storeToRefs(avatar);

const slotItems = computed(() =>
  inventory.value
    .filter(i => i.rewardItem?.slot === props.slot || i.rewardItem?.category === props.slot)
    .map(i => i.rewardItem)
    .filter(Boolean)
);

const isEquipped = computed(() => (code) => {
  if (props.slot === 'accessories') return equipped.value.accessories.includes(code);
  return equipped.value[props.slot] === code;
});
</script>

<template>
  <div>
    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">{{ label }}</p>
    <div v-if="slotItems.length === 0" class="text-sm text-gray-500 italic">
      No tienes items en este slot. ¡Visita la tienda!
    </div>
    <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-3">
      <button v-for="item in slotItems" :key="item.id"
        @click="emit('equip', { slot, item })"
        class="flex flex-col items-center gap-1.5 p-3 rounded-xl border transition-all text-center"
        :class="isEquipped(item.code)
          ? 'border-purple-500 bg-purple-500/20'
          : 'border-white/10 bg-white/5 hover:bg-white/10'">
        <span class="text-2xl">{{ item.metadata?.icon ?? '🎁' }}</span>
        <span class="text-xs text-gray-300 leading-tight">{{ item.name }}</span>
        <span v-if="isEquipped(item.code)" class="text-xs text-purple-400 font-bold">✓ Equipado</span>
      </button>
    </div>
  </div>
</template>
