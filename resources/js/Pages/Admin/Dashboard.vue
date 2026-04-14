<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    bottlenecks: {
        type: Array,
        default: () => [],
    },
});

const formattedTokens = computed(() => Number(props.metrics?.estimated_tokens_saved || 0).toLocaleString('es-MX'));
const formattedHits = computed(() => Number(props.metrics?.estimated_cache_hits || 0).toLocaleString('es-MX'));
</script>

<template>
    <Head title="Admin IA Dashboard" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">Dashboard Administrativo</h1>
                <p class="text-sm text-gray-400">Monitoreo de ahorro de tokens y rendimiento pedagógico del tutor.</p>
            </section>
            <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <article class="rounded-2xl border border-cyan-500/30 bg-cyan-500/10 p-5">
                    <p class="text-[11px] text-cyan-300 uppercase tracking-widest font-black">Explicaciones en Caché</p>
                    <p class="mt-3 text-4xl font-black text-white">{{ metrics.cached_explanations }}</p>
                    <p class="mt-2 text-xs text-gray-300">Respuestas listas para servir sin invocar IA.</p>
                </article>

                <article class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-5">
                    <p class="text-[11px] text-emerald-300 uppercase tracking-widest font-black">Tokens Ahorrados (Est.)</p>
                    <p class="mt-3 text-4xl font-black text-white">{{ formattedTokens }}</p>
                    <p class="mt-2 text-xs text-gray-300">Hits estimados: {{ formattedHits }} x 350 tokens.</p>
                </article>

                <article class="rounded-2xl border border-orange-500/30 bg-orange-500/10 p-5">
                    <p class="text-[11px] text-orange-300 uppercase tracking-widest font-black">Preguntas Críticas</p>
                    <p class="mt-3 text-4xl font-black text-white">{{ metrics.critical_questions }}</p>
                    <p class="mt-2 text-xs text-gray-300">Top preguntas con más variantes de error.</p>
                </article>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/3 overflow-hidden">
                <div class="px-5 py-4 border-b border-white/10">
                    <h3 class="text-sm font-black uppercase tracking-wider text-gray-200">Top 5 Preguntas con más errores</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead class="bg-white/2">
                            <tr>
                                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-widest text-gray-400 font-black">Question ID</th>
                                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-widest text-gray-400 font-black">Variantes de Error</th>
                                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-widest text-gray-400 font-black">Hits Caché</th>
                                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-widest text-gray-400 font-black">Vista Previa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <tr v-for="row in bottlenecks" :key="row.question_id" class="hover:bg-white/2">
                                <td class="px-5 py-4 text-sm font-black text-cyan-300">#{{ row.question_id }}</td>
                                <td class="px-5 py-4 text-sm text-white">{{ row.error_variants }}</td>
                                <td class="px-5 py-4 text-sm text-emerald-300">{{ row.cache_hits }}</td>
                                <td class="px-5 py-4 text-sm text-gray-300">
                                    <span class="line-clamp-2">{{ row.question_preview }}</span>
                                </td>
                            </tr>
                            <tr v-if="!bottlenecks.length">
                                <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">
                                    Aun no hay datos de cuellos de botella en el cache del tutor.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
