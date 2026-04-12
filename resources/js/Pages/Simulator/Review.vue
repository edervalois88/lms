<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/UI/Card.vue';
import UpgradeModal from '@/Components/UI/UpgradeModal.vue';

const props = defineProps({
    exam: Object,
    incorrect_questions: {
        type: Array,
        default: () => [],
    },
});

const index = ref(0);
const tutorLoading = ref(false);
const tutorMessage = ref('');
const tutorResponse = ref('');
const tutorMeta = ref({ from_cache: false, tokens_saved: 0 });
const requestError = ref('');
const showUpgradeModal = ref(false);
const blockedFeature = ref('ai_tutor');

const current = computed(() => props.incorrect_questions[index.value] || null);
const total = computed(() => props.incorrect_questions.length);

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const askTutor = async () => {
    if (!current.value || tutorLoading.value) {
        return;
    }

    tutorLoading.value = true;
    requestError.value = '';

    try {
        const response = await axios.post(route('simulator.review.tutor', props.exam.id), {
            question_id: current.value.id,
            texto_duda: tutorMessage.value.trim(),
        }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
        });

        const chat = response.data?.chat || {};
        tutorResponse.value = String(chat.respuesta_directa || '');
        tutorMeta.value = {
            from_cache: Boolean(chat.from_cache),
            tokens_saved: Number(chat.tokens_saved || 0),
        };
        tutorMessage.value = '';
    } catch (error) {
        if (error?.response?.status === 403 && error?.response?.data?.error === 'freemium_limit_reached') {
            blockedFeature.value = error?.response?.data?.feature || 'ai_tutor';
            showUpgradeModal.value = true;
            return;
        }

        const msg = error?.response?.data?.message;
        requestError.value = typeof msg === 'string' && msg !== ''
            ? msg
            : 'No se pudo contactar al Tutor IA en este momento.';
    } finally {
        tutorLoading.value = false;
    }
};

const goNext = () => {
    if (index.value < total.value - 1) {
        index.value++;
        tutorResponse.value = '';
        requestError.value = '';
    }
};

const goPrev = () => {
    if (index.value > 0) {
        index.value--;
        tutorResponse.value = '';
        requestError.value = '';
    }
};
</script>

<template>
    <Head title="Revisión Táctica - NexusEdu" />

    <AuthenticatedLayout>
        <UpgradeModal :show="showUpgradeModal" :feature="blockedFeature" @close="showUpgradeModal = false" />

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-black text-gray-900">Revisión Táctica</h2>
                    <p class="text-sm text-gray-500 font-semibold">Solo preguntas incorrectas del Simulacro Estricto.</p>
                </div>
                <Link
                    :href="route('simulator.results', exam.id)"
                    class="px-4 py-2 rounded-xl bg-gray-900 text-white font-black text-sm hover:bg-black"
                >
                    Volver a resultados
                </Link>
            </div>
        </template>

        <div class="min-h-screen bg-[radial-gradient(circle_at_20%_0%,#1f2937_0%,#0b0f1a_45%,#020617_100%)] px-4 py-8 md:px-8">
            <div class="max-w-6xl mx-auto">
                <Card glow="orange" class="mb-6">
                    <div class="flex items-center justify-between gap-4 text-white">
                        <p class="text-xs uppercase tracking-[0.2em] font-black text-orange-300">Misión de recuperación</p>
                        <p class="text-sm font-bold text-gray-300">
                            Reactivo {{ Math.min(index + 1, total) }} de {{ total }}
                        </p>
                    </div>
                </Card>

                <div v-if="!current" class="text-center py-16 text-gray-200">
                    <p class="text-2xl font-black">No hay errores por revisar. Excelente misión.</p>
                </div>

                <div v-else class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <Card glow="red" class="xl:col-span-2 text-white">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-300">{{ current.subject_name }} · {{ current.topic_name }}</p>
                        <h3 class="mt-3 text-xl md:text-2xl font-black leading-tight">{{ current.body }}</h3>

                        <div class="mt-5 space-y-3">
                            <div
                                v-for="(opt, i) in current.options"
                                :key="i"
                                class="rounded-2xl border px-4 py-3 text-sm md:text-base"
                                :class="[
                                    i === current.correct_index ? 'border-emerald-400 bg-emerald-500/15 text-emerald-100' : '',
                                    i === current.selected_index && i !== current.correct_index ? 'border-rose-400 bg-rose-500/15 text-rose-100' : '',
                                    i !== current.correct_index && i !== current.selected_index ? 'border-white/10 bg-white/5 text-gray-200' : '',
                                ]"
                            >
                                <span class="font-black mr-2">{{ String.fromCharCode(65 + i) }}.</span>
                                {{ opt }}
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-orange-400/40 bg-orange-500/10 p-4">
                            <p class="text-xs uppercase tracking-widest font-black text-orange-300">Explicación oficial</p>
                            <p class="mt-2 text-sm text-orange-100 leading-relaxed">{{ current.explanation || 'Sin explicación oficial disponible.' }}</p>
                        </div>
                    </Card>

                    <Card glow="green" class="text-white">
                        <p class="text-xs uppercase tracking-[0.2em] font-black text-emerald-300">Tutor IA</p>
                        <p class="mt-2 text-sm text-gray-300">Pregunta por qué fallaste y cómo resolver reactivos similares.</p>

                        <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4 min-h-36">
                            <p class="text-sm whitespace-pre-line text-gray-100">
                                {{ tutorResponse || 'Activa el análisis para recibir una explicación personalizada.' }}
                            </p>
                            <p v-if="tutorMeta.from_cache" class="mt-3 text-xs font-bold text-emerald-300">
                                Respuesta instantánea desde caché · Tokens ahorrados: {{ tutorMeta.tokens_saved }}
                            </p>
                        </div>

                        <form class="mt-4 space-y-3" @submit.prevent="askTutor">
                            <textarea
                                v-model="tutorMessage"
                                rows="3"
                                placeholder="¿En qué me confundí exactamente?"
                                class="w-full rounded-xl border border-white/10 bg-black/30 text-sm text-white px-3 py-2 placeholder:text-gray-500"
                            />
                            <button
                                type="submit"
                                :disabled="tutorLoading"
                                class="w-full rounded-xl bg-emerald-500 text-black font-black py-3 uppercase tracking-wider hover:bg-emerald-400 disabled:opacity-60"
                            >
                                {{ tutorLoading ? 'Analizando...' : 'Analizar con IA' }}
                            </button>
                        </form>

                        <p v-if="requestError" class="mt-3 text-xs font-bold text-rose-300">{{ requestError }}</p>
                    </Card>
                </div>

                <div v-if="current" class="mt-6 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="goPrev"
                        :disabled="index <= 0"
                        class="px-5 py-3 rounded-xl border border-white/15 bg-white/5 text-white font-black disabled:opacity-40"
                    >
                        Anterior
                    </button>

                    <button
                        type="button"
                        @click="goNext"
                        :disabled="index >= total - 1"
                        class="px-5 py-3 rounded-xl bg-orange-500 text-black font-black uppercase tracking-wider shadow-[0_0_15px_rgba(255,165,0,0.5)] disabled:opacity-40"
                    >
                        Siguiente error
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
