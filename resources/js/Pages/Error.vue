<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: Number,
});

const title = computed(() => {
    return {
        503: 'Servicio no disponible',
        500: 'Error del servidor',
        404: 'Página no encontrada',
        403: 'Acceso prohibido',
    }[props.status];
});

const description = computed(() => {
    return {
        503: 'Lo sentimos, estamos realizando mantenimiento. Regresa pronto.',
        500: '¡Ups! Algo salió mal en nuestros servidores.',
        404: 'Lo sentimos, la página que buscas no existe.',
        403: 'No tienes los permisos necesarios para acceder a este recurso.',
    }[props.status];
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <span class="text-9xl font-black text-orange-100">{{ status }}</span>
            </div>
            <h1 class="text-3xl font-black text-gray-900 mb-4">{{ title }}</h1>
            <p class="text-lg text-gray-500 mb-10">{{ description }}</p>
            <Link 
                :href="route('dashboard')" 
                class="bg-orange-500 text-white px-8 py-4 rounded-2xl font-black text-lg hover:bg-orange-600 transition-all shadow-lg"
            >
                Regresar al Inicio
            </Link>
        </div>
    </div>
</template>
