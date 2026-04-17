<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ShopCatalog from '@/Components/Gamification/Shop/ShopCatalog.vue';
import CurrencyDisplay from '@/Components/Gamification/Currency/CurrencyDisplay.vue';
</script>

<template>
  <Head title="Tienda de Cosmética" />
  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto px-4 py-8">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-white">Tienda</h1>
          <p class="text-sm text-gray-400 mt-1">Personaliza tu avatar con Gold ganado estudiando</p>
        </div>
        <CurrencyDisplay />
      </div>
      <ShopCatalog />
    </div>
  </AuthenticatedLayout>
</template>
