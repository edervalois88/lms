<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    date: String,
    window: Object,
    totals_today: Object,
    current_minute: Object,
    operations: Object,
    limits_reference: Object,
});

const operationsList = computed(() => {
    return Object.entries(props.operations || {}).map(([key, value]) => ({
        name: key.replace(/_/g, ' ').toUpperCase(),
        ...value,
    }));
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('es-MX').format(num);
};
</script>

<template>
    <Head title="Métricas IA" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Header -->
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">🤖 Métricas IA</h1>
                <p class="text-sm text-gray-400">Monitoreo de uso de APIs, caché y rendimiento del Tutor.</p>
            </section>

            <!-- Date & Window Info -->
            <section class="glass-morphism rounded-2xl border border-white/10 p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Fecha</p>
                    <p class="text-lg font-black text-white">{{ date }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Ventana Actual</p>
                    <p class="text-lg font-black text-orange-400">{{ window.current_minute }}</p>
                </div>
            </section>

            <!-- Totales Today -->
            <section class="space-y-3">
                <h2 class="text-xl font-black uppercase tracking-tight">📊 Totales del Día</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <article class="glass-morphism rounded-2xl border border-white/10 p-4 space-y-1">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Requests</p>
                        <p class="text-2xl font-black text-blue-400">{{ formatNumber(totals_today.requests) }}</p>
                    </article>
                    <article class="glass-morphism rounded-2xl border border-white/10 p-4 space-y-1">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Tokens</p>
                        <p class="text-2xl font-black text-green-400">{{ formatNumber(totals_today.tokens) }}</p>
                    </article>
                    <article class="glass-morphism rounded-2xl border border-white/10 p-4 space-y-1">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Errores</p>
                        <p class="text-2xl font-black" :class="totals_today.errors > 0 ? 'text-red-400' : 'text-green-400'">
                            {{ formatNumber(totals_today.errors) }}
                        </p>
                    </article>
                    <article class="glass-morphism rounded-2xl border border-white/10 p-4 space-y-1">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Cache Hits</p>
                        <p class="text-2xl font-black text-emerald-400">{{ formatNumber(totals_today.cache_hits) }}</p>
                    </article>
                    <article class="glass-morphism rounded-2xl border border-white/10 p-4 space-y-1">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Cache Misses</p>
                        <p class="text-2xl font-black text-yellow-400">{{ formatNumber(totals_today.cache_misses) }}</p>
                    </article>
                    <article class="glass-morphism rounded-2xl border border-white/10 p-4 space-y-1">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Hit Rate</p>
                        <p class="text-2xl font-black text-purple-400">{{ totals_today.cache_hit_rate_percent }}%</p>
                    </article>
                </div>
            </section>

            <!-- Current Minute -->
            <section class="glass-morphism rounded-2xl border border-white/10 p-6 space-y-4">
                <h3 class="text-lg font-black uppercase tracking-tight">⏱️ Minuto Actual</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-sm text-gray-400 uppercase font-bold">Requests este minuto</p>
                        <p class="text-3xl font-black text-blue-400 mt-2">{{ current_minute.requests }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-sm text-gray-400 uppercase font-bold">Tokens este minuto</p>
                        <p class="text-3xl font-black text-green-400 mt-2">{{ formatNumber(current_minute.tokens) }}</p>
                    </div>
                </div>
            </section>

            <!-- Operations Breakdown -->
            <section class="space-y-3">
                <h2 class="text-xl font-black uppercase tracking-tight">📈 Desglose por Operación</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <article v-for="operation in operationsList" :key="operation.name" class="glass-morphism rounded-2xl border border-white/10 p-6 space-y-4">
                        <h4 class="text-lg font-black text-orange-300 uppercase">{{ operation.name }}</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Requests:</span>
                                <span class="text-white font-bold">{{ formatNumber(operation.requests) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Tokens:</span>
                                <span class="text-green-400 font-bold">{{ formatNumber(operation.tokens) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Errores:</span>
                                <span :class="operation.errors > 0 ? 'text-red-400' : 'text-green-400'" class="font-bold">{{ operation.errors }}</span>
                            </div>
                            <div class="border-t border-white/10 pt-2 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Cache Hits:</span>
                                    <span class="text-emerald-400 font-bold">{{ formatNumber(operation.cache_hits) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Cache Misses:</span>
                                    <span class="text-yellow-400 font-bold">{{ formatNumber(operation.cache_misses) }}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Limits Reference -->
            <section class="glass-morphism rounded-2xl border border-white/10 p-6 space-y-4 bg-yellow-500/5 border-yellow-500/30">
                <h3 class="text-lg font-black uppercase tracking-tight text-yellow-400">⚠️ Límites de Referencia</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-3 rounded-lg bg-white/5">
                        <p class="text-xs text-gray-400 uppercase font-bold">Requests/Min</p>
                        <p class="text-xl font-black text-yellow-300 mt-1">{{ limits_reference.requests_per_minute }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-white/5">
                        <p class="text-xs text-gray-400 uppercase font-bold">Tokens/Min</p>
                        <p class="text-xl font-black text-yellow-300 mt-1">{{ formatNumber(limits_reference.tokens_per_minute) }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-white/5">
                        <p class="text-xs text-gray-400 uppercase font-bold">Requests/Día</p>
                        <p class="text-xl font-black text-yellow-300 mt-1">{{ formatNumber(limits_reference.requests_per_day) }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-white/5">
                        <p class="text-xs text-gray-400 uppercase font-bold">Tokens/Día</p>
                        <p class="text-xl font-black text-yellow-300 mt-1">{{ formatNumber(limits_reference.tokens_per_day) }}</p>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.glass-morphism {
    background: rgba(255, 255, 255, 0.04);
    backdrop-filter: blur(10px);
}
</style>
