<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    subjects: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const selectedSubjectId = ref('');
const searching = ref(false);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const toastMessage = ref('');

const form = ref({
    id: null,
    stem: '',
    options: ['', '', '', ''],
    correct_index: 0,
    ai_explanation: '',
    subject_name: '',
    topic_name: '',
});

const canSave = computed(() => {
    return Boolean(form.value.id)
        && form.value.stem.trim().length > 0
        && form.value.options.every((opt) => String(opt).trim().length > 0)
        && form.value.ai_explanation.trim().length > 0;
});

const showToast = (text) => {
    toastMessage.value = text;
    window.setTimeout(() => {
        toastMessage.value = '';
    }, 2200);
};

const doSearch = async () => {
    loadError.value = '';
    saveError.value = '';
    searching.value = true;

    try {
        const response = await axios.get(route('admin.curation.search'), {
            params: {
                query: searchQuery.value,
                subject_id: selectedSubjectId.value || undefined,
            },
            headers: { Accept: 'application/json' },
        });

        const question = response.data?.question;
        const cache = response.data?.cache;

        form.value = {
            id: question?.id ?? null,
            stem: question?.stem ?? '',
            options: Array.isArray(question?.options) && question.options.length === 4
                ? [...question.options]
                : ['', '', '', ''],
            correct_index: Number(question?.correct_index ?? 0),
            ai_explanation: cache?.explicacion_ia ?? '',
            subject_name: question?.subject_name ?? '',
            topic_name: question?.topic_name ?? '',
        };

        if (!cache?.explicacion_ia) {
            form.value.ai_explanation = 'Explicación pendiente de curación humana.';
        }
    } catch (error) {
        const message = error?.response?.data?.message;
        loadError.value = typeof message === 'string' && message !== ''
            ? message
            : 'No se pudo cargar la pregunta.';
    } finally {
        searching.value = false;
    }
};

const saveChanges = async () => {
    if (!canSave.value || saving.value) {
        return;
    }

    saveError.value = '';
    saving.value = true;

    try {
        await axios.put(route('admin.curation.update', form.value.id), {
            stem: form.value.stem,
            options: form.value.options,
            correct_index: form.value.correct_index,
            ai_explanation: form.value.ai_explanation,
        }, {
            headers: { Accept: 'application/json' },
        });

        showToast('Cambios guardados correctamente.');
    } catch (error) {
        const message = error?.response?.data?.message;
        saveError.value = typeof message === 'string' && message !== ''
            ? message
            : 'No fue posible guardar los cambios.';
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Head title="Panel de Curación de Contenido" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tight">Panel de Curación de Contenido</h2>
                    <p class="text-sm text-gray-400">Edita preguntas y sobrescribe explicaciones del Tutor IA.</p>
                </div>
                <Link
                    :href="route('admin.dashboard')"
                    class="rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-xs font-black uppercase tracking-wider text-gray-200 hover:bg-white/10"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <section class="rounded-2xl border border-white/10 bg-slate-900/85 p-5 md:p-6">
                <p class="text-[11px] font-black uppercase tracking-[0.25em] text-cyan-300">Buscador</p>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Buscar por ID numérico o palabras clave..."
                        class="md:col-span-7 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400/40"
                        @keyup.enter="doSearch"
                    />
                    <select
                        v-model="selectedSubjectId"
                        class="md:col-span-3 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-cyan-400/40"
                    >
                        <option value="">Todas las materias</option>
                        <option v-for="subject in subjects" :key="subject.id" :value="String(subject.id)">
                            {{ subject.name }}
                        </option>
                    </select>
                    <button
                        type="button"
                        :disabled="searching"
                        class="md:col-span-2 rounded-xl bg-linear-to-r from-cyan-400 to-blue-500 text-black font-black uppercase tracking-wider text-sm px-4 py-3 disabled:opacity-50"
                        @click="doSearch"
                    >
                        {{ searching ? 'Buscando...' : 'Buscar' }}
                    </button>
                </div>
                <p v-if="loadError" class="mt-3 text-sm font-semibold text-rose-300">{{ loadError }}</p>
            </section>

            <section class="rounded-2xl border border-white/10 bg-slate-900/85 p-5 md:p-6 space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-emerald-300">Editor de Pregunta</p>
                    <p v-if="form.id" class="text-xs font-black text-cyan-300">#{{ form.id }} · {{ form.subject_name }} · {{ form.topic_name }}</p>
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black uppercase tracking-widest text-gray-400">Texto de la pregunta</label>
                    <textarea
                        v-model="form.stem"
                        rows="5"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        placeholder="Escribe o corrige el texto de la pregunta..."
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div v-for="(opt, idx) in form.options" :key="idx" class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-gray-400">Opción {{ String.fromCharCode(65 + idx) }}</label>
                        <input
                            v-model="form.options[idx]"
                            type="text"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                            :placeholder="`Texto de opción ${String.fromCharCode(65 + idx)}`"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-gray-400">Respuesta correcta</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button
                            v-for="idx in [0,1,2,3]"
                            :key="idx"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-sm font-bold"
                            :class="form.correct_index === idx ? 'border-emerald-400 bg-emerald-500/20 text-emerald-200' : 'border-white/10 bg-white/5 text-gray-300'"
                            @click="form.correct_index = idx"
                        >
                            {{ String.fromCharCode(65 + idx) }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-slate-900/85 p-5 md:p-6 space-y-3">
                <p class="text-[11px] font-black uppercase tracking-[0.25em] text-fuchsia-300">Curación de IA</p>
                <textarea
                    v-model="form.ai_explanation"
                    rows="8"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-400/40"
                    placeholder="Reescribe o mejora la explicación del Tutor IA..."
                />
                <p v-if="saveError" class="text-sm font-semibold text-rose-300">{{ saveError }}</p>
            </section>
        </div>

        <button
            type="button"
            :disabled="!canSave || saving"
            class="fixed right-6 bottom-6 z-40 rounded-full bg-linear-to-r from-emerald-400 to-cyan-400 text-black px-6 py-3 font-black uppercase tracking-wider shadow-[0_0_22px_rgba(16,185,129,0.4)] disabled:opacity-50"
            @click="saveChanges"
        >
            {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
        </button>

        <div v-if="toastMessage" class="fixed top-6 right-6 z-50 rounded-xl border border-emerald-400/40 bg-emerald-500/20 px-4 py-3 text-sm font-black text-emerald-100 shadow-[0_0_16px_rgba(16,185,129,0.35)]">
            {{ toastMessage }}
        </div>
    </AuthenticatedLayout>
</template>
