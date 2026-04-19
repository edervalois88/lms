<script setup>
import { ref, onMounted } from 'vue';
import { useShopStore } from '@/Stores/gamification/shopStore';
import { useAvatarStore } from '@/Stores/gamification/avatarStore';
import { storeToRefs } from 'pinia';
import CosmeticSelector from './Cosmetics/CosmeticSelector.vue';
import Avatar from '@/Components/Gamification/Avatar.vue';

const shop = useShopStore();
const avatar = useAvatarStore();
const { equipped } = storeToRefs(avatar);
const previewState = ref('idle');

const slots = [
  { slot: 'color', label: 'Color base' },
  { slot: 'outfit', label: 'Ropa' },
  { slot: 'accessories', label: 'Accesorios' },
  { slot: 'pet', label: 'Mascota' },
  { slot: 'background', label: 'Fondo' },
];

async function onEquip({ slot, item }) {
  const owned = shop.inventory.find(i => i.rewardItem?.id === item.id);
  if (!owned) return;
  const result = await shop.equip(owned.id);
  if (result?.ok) {
    avatar.setEquipped(slot, item.code);
  }
}

onMounted(() => shop.fetchInventory());
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="flex flex-col items-center justify-start gap-4">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Preview</p>
      <div class="relative w-52 h-52">
        <div class="absolute inset-0 rounded-full opacity-30"
          style="background: conic-gradient(from 0deg, #667eea, #764ba2, #a855f7, #667eea);" />
        <div class="relative z-10 flex items-center justify-center h-full">
          <Avatar :equipped="equipped" :state="previewState" size="lg" />
        </div>
      </div>
      <div class="text-center text-xs text-gray-500 space-y-1">
        <p>Ropa: <span class="text-white">{{ equipped.outfit }}</span></p>
        <p>Mascota: <span class="text-white">{{ equipped.pet }}</span></p>
      </div>
    </div>
    <div class="md:col-span-2 space-y-6">
      <CosmeticSelector v-for="slotDef in slots" :key="slotDef.slot"
        :slot="slotDef.slot" :label="slotDef.label" @equip="onEquip" />
    </div>
  </div>
</template>
