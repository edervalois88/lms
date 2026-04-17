<script setup>
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { storeToRefs } from 'pinia';

const currency = useCurrencyStore();
const { gold, currentLevel } = storeToRefs(currency);
</script>

<template>
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-1.5 px-3 py-1 bg-yellow-500/10 border border-yellow-500/20 rounded-full">
      <span class="text-base">🪙</span>
      <span class="text-sm font-bold text-yellow-400">{{ gold.toLocaleString() }}</span>
    </div>
    <div class="flex items-center gap-1.5 px-3 py-1 bg-purple-500/10 border border-purple-500/20 rounded-full">
      <span class="text-base">⭐</span>
      <span class="text-sm font-bold text-purple-400">Nv. {{ currentLevel }}</span>
    </div>
  </div>
</template>
