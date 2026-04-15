<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    questions: {
        type: Object,
        required: true,
    },
    subjects: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', subject_id: '' }),
    },
});

const selectedSubject = ref(props.filters.subject_id || '');
const searchQuery = ref(props.filters.search || '');

const onSearch = () => {
    router.get(route('admin.questions.index'), {
        search: searchQuery.value,
        subject_id: selectedSubject.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const getDifficultyColor = (difficulty) => {
    const colors = {
        1: 'green',
        2: 'blue',
        3: 'yellow',
        4: 'orange',
        5: 'red',
    };
    return colors[difficulty] || 'gray';
};

const getDifficultyLabel = (difficulty) => {
    const labels = {
        1: 'Muy Fácil',
        2: 'Fácil',
        3: 'Medio',
        4: 'Difícil',
        5: 'Muy Difícil',
    };
    return labels[difficulty] || 'N/A';
};
</script>

<template>
    <Head title="Gestión de Preguntas" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Header -->
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">❓ Gestión de Preguntas</h1>
                <p class="text-sm text-gray-400">Administra el banco de preguntas, edita contenido y valida calidad.</p>
            </section>

            <!-- Stats -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Total Preguntas</p>
                    <p class="text-3xl font-black mt-2 text-orange-400">{{ questions.total || 0 }}</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Activas</p>
                    <p class="text-3xl font-black mt-2 text-green-400">0</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Generadas por IA</p>
                    <p class="text-3xl font-black mt-2 text-blue-400">0</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Sin Revisar</p>
                    <p class="text-3xl font-black mt-2 text-red-400">0</p>
                </article>
            </section>

            <!-- Filters & Search -->
            <section class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Buscar preguntas..."
                        class="px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-orange-500/50 focus:ring-2 focus:ring-orange-500/20 transition-all"
                    />
                    <select
                        v-model="selectedSubject"
                        class="px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:outline-none focus:border-orange-500/50 focus:ring-2 focus:ring-orange-500/20 transition-all"
                    >
                        <option value="">Todas las asignaturas</option>
                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                            {{ subject.name }}
                        </option>
                    </select>
                    <button
                        @click="onSearch"
                        class="px-6 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold uppercase tracking-wider transition-all duration-200"
                    >
                        Buscar
                    </button>
                </div>

                <!-- Questions Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Pregunta</th>
                                <th class="text-left px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Asignatura</th>
                                <th class="text-center px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Dificultad</th>
                                <th class="text-center px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Tipo</th>
                                <th class="text-right px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="question in questions.data" :key="question.id" class="border-b border-white/5 hover:bg-white/3 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="max-w-xs">
                                        <div class="font-semibold text-white truncate">{{ question.stem }}</div>
                                        <div class="text-xs text-gray-500 mt-1">ID: {{ question.id }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-300">{{ question.topic?.subject?.name || 'N/A' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="`bg-${getDifficultyColor(question.difficulty)}-500/20 text-${getDifficultyColor(question.difficulty)}-300`"
                                        class="px-2 py-1 rounded text-xs font-bold"
                                    >
                                        {{ getDifficultyLabel(question.difficulty) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold" :class="{
                                        'bg-purple-500/20 text-purple-300': question.is_ai_generated,
                                        'bg-green-500/20 text-green-300': !question.is_ai_generated,
                                    }">
                                        {{ question.is_ai_generated ? 'IA' : 'Manual' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.questions.show', question.id)"
                                        class="text-orange-400 hover:text-orange-300 font-semibold text-sm mr-2"
                                    >
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!questions.data || questions.data.length === 0" class="text-center py-8">
                    <p class="text-gray-400">No se encontraron preguntas</p>
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
