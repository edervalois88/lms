<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },
});

const charts = [
    { title: 'Usuarios Activos (7 días)', value: '234', icon: '📈', color: 'green' },
    { title: 'Exámenes Completados', value: '1,248', icon: '✅', color: 'blue' },
    { title: 'Preguntas Respondidas', value: '12,456', icon: '❓', color: 'orange' },
    { title: 'Tiempo Promedio Sesión', value: '45 min', icon: '⏱️', color: 'purple' },
];
</script>

<template>
    <Head title="Analíticas" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Header -->
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">📈 Analíticas</h1>
                <p class="text-sm text-gray-400">Reportes detallados del rendimiento del sistema y comportamiento de usuarios.</p>
            </section>

            <!-- Key Metrics -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <article v-for="(chart, idx) in charts" :key="idx" class="glass-morphism rounded-2xl border border-white/10 p-6 space-y-3 hover:border-white/20 transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-black uppercase tracking-wider">{{ chart.title }}</p>
                            <p class="text-3xl font-black mt-2" :class="{
                                'text-green-400': chart.color === 'green',
                                'text-blue-400': chart.color === 'blue',
                                'text-orange-400': chart.color === 'orange',
                                'text-purple-400': chart.color === 'purple',
                            }">{{ chart.value }}</p>
                        </div>
                        <span class="text-3xl">{{ chart.icon }}</span>
                    </div>
                </article>
            </section>

            <!-- Charts Section -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Chart 1 -->
                <article class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                    <h3 class="text-lg font-black uppercase tracking-tight">Usuarios por Día (Últimos 7 días)</h3>
                    <div class="h-64 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center">
                        <p class="text-gray-500">Gráfico de barras (Integración con librería pendiente)</p>
                    </div>
                </article>

                <!-- Chart 2 -->
                <article class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                    <h3 class="text-lg font-black uppercase tracking-tight">Exámenes por Asignatura</h3>
                    <div class="h-64 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center">
                        <p class="text-gray-500">Gráfico de pastel (Integración con librería pendiente)</p>
                    </div>
                </article>

                <!-- Chart 3 -->
                <article class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                    <h3 class="text-lg font-black uppercase tracking-tight">Tasa de Éxito por Tema</h3>
                    <div class="h-64 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center">
                        <p class="text-gray-500">Gráfico de líneas (Integración con librería pendiente)</p>
                    </div>
                </article>

                <!-- Chart 4 -->
                <article class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                    <h3 class="text-lg font-black uppercase tracking-tight">Distribución de Dificultad</h3>
                    <div class="h-64 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center">
                        <p class="text-gray-500">Gráfico de barras apiladas (Integración con librería pendiente)</p>
                    </div>
                </article>
            </section>

            <!-- Detailed Tables -->
            <section class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                <h3 class="text-xl font-black uppercase tracking-tight">Sesiones Recientes</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Usuario</th>
                                <th class="text-left px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Actividad</th>
                                <th class="text-center px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Duración</th>
                                <th class="text-center px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="i in 5" :key="i" class="border-b border-white/5 hover:bg-white/3 transition-colors">
                                <td class="px-4 py-3 font-semibold text-white">Usuario {{ i }}</td>
                                <td class="px-4 py-3 text-gray-300">Realizó examen</td>
                                <td class="px-4 py-3 text-center">{{ 30 + i * 5 }} min</td>
                                <td class="px-4 py-3 text-center text-gray-500">Hace {{ i }} horas</td>
                            </tr>
                        </tbody>
                    </table>
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
