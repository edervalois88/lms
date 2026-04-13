<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mastery: {
        type: Array,
        default: () => []
    },
    exams_history: {
        type: Array,
        default: () => []
    },
    exams_pagination: {
        type: Object,
        default: () => ({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
    },
    projection: {
        type: Object,
        default: () => ({ projected_score: 0, confidence: 'Baja' })
    },
    streak_days: {
        type: Number,
        default: 0
    },
    weekly_stats: {
        type: Object,
        default: () => ({ questions_answered: 0 })
    }
});

// Calculate aggregate stats
const totalQuestions = computed(() => {
    return props.mastery.reduce((acc, m) => acc + (m.total_attempts || 0), 0);
});

const avgAccuracy = computed(() => {
    const totalCorrect = props.mastery.reduce((acc, m) => acc + (m.correct_attempts || 0), 0);
    return totalQuestions.value > 0 ? Math.round((totalCorrect / totalQuestions.value) * 100) : 0;
});

const streak = computed(() => props.streak_days || 0);

const formatDate = (dateString) => {
    if (!dateString) return 'Sin fecha';

    return new Date(dateString).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const examsHistory = computed(() => props.exams_history || []);
const confidenceLabel = computed(() => props.projection?.confidence || 'Baja');
</script>

<template>
    <Head title="Mi Progreso - NexusEdu" />

    <div class="progress-page app-shell min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            
            <header class="mb-12 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 mb-2">Mi <span class="text-orange-500">Progreso</span></h1>
                    <p class="text-lg text-gray-500">Visualiza tu evolución y prepárate para el éxito.</p>
                </div>
                <div class="flex gap-4">
                    <div class="nx-panel px-6 py-4 rounded-3xl flex items-center">
                        <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center mr-4">
                            <i class="fa-solid fa-fire"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Racha</p>
                            <p class="text-xl font-black text-gray-900">{{ streak }} días</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="nx-panel p-8 rounded-3xl">
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Total Preguntas</p>
                    <h3 class="text-4xl font-black text-gray-900">{{ totalQuestions }}</h3>
                    <div class="mt-4 flex items-center text-green-500 text-sm font-bold">
                        <i class="fa-solid fa-arrow-up mr-2"></i>
                        <span>{{ weekly_stats.questions_answered || 0 }} respuestas esta semana</span>
                    </div>
                </div>
                <div class="nx-panel p-8 rounded-3xl">
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Precisión Media</p>
                    <h3 class="text-4xl font-black text-gray-900">{{ avgAccuracy }}%</h3>
                    <div class="mt-4 w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-orange-500 h-full" :style="{ width: avgAccuracy + '%' }"></div>
                    </div>
                </div>
                <div class="nx-panel p-8 rounded-3xl">
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Nivel Global</p>
                    <h3 class="text-4xl font-black text-gray-900">{{ confidenceLabel }}</h3>
                    <p class="mt-4 text-gray-500 text-sm italic">Confianza estimada de tu proyección actual.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Mastery by Subject -->
                <div class="nx-panel p-8 rounded-3xl">
                    <h2 class="text-2xl font-black text-gray-900 mb-8 flex items-center">
                        <i class="fa-solid fa-chart-line mr-3 text-orange-600"></i>
                        Dominio por Materia
                    </h2>

                    <div v-if="mastery.length === 0" class="py-12 text-center">
                        <i class="fa-solid fa-ghost text-4xl text-gray-200 mb-4 block"></i>
                        <p class="text-gray-400">Aún no tienes datos suficientes. ¡Comienza un quiz!</p>
                    </div>

                    <div v-else class="space-y-8">
                        <div v-for="item in mastery" :key="item.id || item.subject" class="group">
                            <div class="flex justify-between items-end mb-3">
                                <div>
                                    <h4 class="font-bold text-gray-900 group-hover:text-orange-600 transition-colors">
                                        {{ item.subject || 'Materia' }}
                                    </h4>
                                    <p class="text-xs text-gray-400 uppercase">Tendencia: {{ item.trend || 'stable' }}</p>
                                </div>
                                <span class="text-lg font-black" :style="{ color: item.subject_color || '#F97316' }">
                                    {{ Math.round((item.mastery_score || 0) * 10) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-50 h-3 rounded-full overflow-hidden border border-gray-100">
                                <div 
                                    class="h-full transition-all duration-1000 ease-out shadow-inner"
                                    :style="{ 
                                        width: (item.mastery_score || 0) * 10 + '%',
                                        backgroundColor: item.subject_color || '#F97316'
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam History -->
                <div class="nx-panel p-8 rounded-3xl">
                    <h2 class="text-2xl font-black text-gray-900 mb-8 flex items-center">
                        <i class="fa-solid fa-history mr-3 text-orange-600"></i>
                        Historial de Exámenes
                    </h2>

                    <div v-if="examsHistory.length === 0" class="py-12 text-center">
                        <p class="text-gray-400 italic">No has realizado simulacros todavía.</p>
                    </div>

                    <div v-else class="overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50">
                                    <th class="pb-4">Fecha</th>
                                    <th class="pb-4">Tipo</th>
                                    <th class="pb-4">Resultado</th>
                                    <th class="pb-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="exam in examsHistory" :key="exam.id" class="group">
                                    <td class="py-5 font-medium text-gray-600">{{ formatDate(exam.created_at) }}</td>
                                    <td class="py-5">
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold uppercase border border-white/10">
                                            {{ exam.type }}
                                        </span>
                                    </td>
                                    <td class="py-5">
                                        <span class="font-black text-gray-900">{{ exam.score ?? '--' }}/120</span>
                                    </td>
                                    <td class="py-5 text-right">
                                        <Link
                                            :href="route('simulator.results', exam.id)"
                                            class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-orange-500/15 hover:text-orange-300 transition-all"
                                        >
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mt-6 flex items-center justify-between text-sm text-gray-500">
                            <span>
                                Mostrando {{ examsHistory.length }} de {{ exams_pagination.total }} simulacros
                            </span>
                            <span>
                                Página {{ exams_pagination.current_page }} / {{ exams_pagination.last_page }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Promotion / CTA -->
            <div class="mt-12 bg-linear-to-r from-orange-500 to-red-600 rounded-3xl p-10 text-white flex flex-col md:flex-row items-center justify-between shadow-2xl overflow-hidden relative">
                <div class="relative z-10 text-center md:text-left mb-8 md:mb-0">
                    <h2 class="text-3xl font-black mb-2">¿Listo para el siguiente nivel?</h2>
                    <p class="text-orange-100 text-lg">Inicia un simulacro completo y proyecta tu puntaje real UNAM.</p>
                </div>
                <Link
                    :href="route('simulator.index')"
                    class="relative z-10 bg-white text-orange-600 px-10 py-4 rounded-2xl font-black text-lg hover:bg-orange-50 transition-colors shadow-lg shadow-black/10"
                >
                    Iniciar Simulacro
                </Link>
                <!-- Abstract decorations -->
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-black/10 rounded-full blur-3xl"></div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

:global(.progress-page) {
    color: var(--app-text);
}

:global(.progress-page .bg-gray-50) {
    background-color: color-mix(in srgb, var(--app-bg) 88%, white 12%) !important;
}

:global(.progress-page .bg-white) {
    background: var(--app-card) !important;
    border-color: var(--app-card-border) !important;
}

:global(.progress-page .text-gray-900) {
    color: var(--app-text-strong) !important;
}

:global(.progress-page .text-gray-600),
:global(.progress-page .text-gray-500),
:global(.progress-page .text-gray-400) {
    color: var(--app-text-muted) !important;
}

:global(.progress-page .bg-gray-100) {
    background-color: color-mix(in srgb, var(--app-text-muted) 18%, transparent 82%) !important;
}

:global(.progress-page .border-gray-100),
:global(.progress-page .border-gray-50) {
    border-color: var(--app-card-border) !important;
}
</style>
