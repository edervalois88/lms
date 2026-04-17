<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarCustomizer from '@/Components/Gamification/AvatarCustomizer.vue';
</script>

<template>
  <Head title="Mi Avatar" />
  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Mi Avatar</h1>
        <p class="text-sm text-gray-400 mt-1">Equipa la cosmética de tu inventario</p>
      </div>
      <AvatarCustomizer />
    </div>
  </AuthenticatedLayout>
</template>
