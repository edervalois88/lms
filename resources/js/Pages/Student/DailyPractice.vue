<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuestionCard from '@/Components/Quiz/QuestionCard.vue';
import AnswerFeedback from '@/Components/Quiz/AnswerFeedback.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    questions: Array,
    total: Number,
    srs_count: Number,
});

const currentIndex = ref(0);
const currentQuestion = ref(props.questions[0] || null);
const showFeedback = ref(false);
const isCorrect = ref(false);
const submitting = ref(false);
const correctCount = ref(0);
const xpEarned = ref(0);
const apiError = ref('');

const progress = computed(() =>
    props.total > 0 ? Math.round((currentIndex.value / props.total) * 100) : 0
);

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const handleAnswer = async (selectedIndex) => {
    if (submitting.value || !currentQuestion.value) return;

    apiError.value = '';
    submitting.value = true;
    const answerIsCorrect = selectedIndex === currentQuestion.value.correct_index;

    const quality = answerIsCorrect ? 5 : 1;

    try {
        const response = await fetch(route('review.answer', currentQuestion.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ quality, source: 'daily' }),
        });

        if (!response.ok) {
            throw new Error('No se pudo guardar la respuesta.');
        }

        const payload = await response.json();

        isCorrect.value = answerIsCorrect;
        showFeedback.value = true;

        if (answerIsCorrect) {
            correctCount.value++;
        }

        xpEarned.value += Number(payload?.xp_awarded ?? 0);
    } catch (_error) {
        apiError.value = 'No pudimos guardar tu respuesta. Reintenta para continuar.';
    } finally {
        submitting.value = false;
    }
};

const nextQuestion = () => {
    showFeedback.value = false;
    currentIndex.value++;
    if (currentIndex.value < props.questions.length) {
        currentQuestion.value = props.questions[currentIndex.value];
    } else {
        currentQuestion.value = null;
    }
};
</script>

<template>
    <Head title="Práctica Diaria - NexusEdu" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Práctica Diaria</h2>
                <div class="flex items-center gap-3">
                    <div class="px-4 py-1 bg-orange-100 text-orange-600 rounded-full text-xs font-black uppercase">
                        {{ total }} preguntas
                    </div>
                    <div v-if="srs_count > 0" class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-bold">
                        {{ srs_count }} repaso SRS
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-3xl mx-auto">

                <!-- Progress bar -->
                <div v-if="currentQuestion" class="mb-8">
                    <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
                        <span>{{ currentIndex + 1 }} / {{ total }}</span>
                        <span class="text-orange-500">+{{ xpEarned }} XP</span>
                    </div>
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-orange-500 rounded-full transition-all duration-500"
                            :style="{ width: progress + '%' }"
                        />
                    </div>
                </div>

                <!-- Question in progress -->
                <div v-if="currentQuestion" class="space-y-6">
                    <div
                        v-if="apiError"
                        class="rounded-2xl border border-rose-500/40 bg-rose-500/10 text-rose-300 px-4 py-3 text-sm font-bold"
                    >
                        {{ apiError }}
                    </div>

                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest px-2">
                        Materia: {{ currentQuestion.topic?.subject?.name ?? currentQuestion.topic?.name ?? '—' }}
                    </p>

                    <QuestionCard
                        :question="currentQuestion"
                        :disabled="showFeedback"
                        @answered="handleAnswer"
                    />

                    <div v-if="showFeedback" class="animate-fade-in">
                        <AnswerFeedback
                            :correct="isCorrect"
                            :explanation="currentQuestion.explanation"
                            :concept="currentQuestion.topic?.name ?? 'Tema'"
                            :topic-detail="currentQuestion.topic?.description"
                            @next="nextQuestion"
                        />
                    </div>
                </div>

                <!-- Completion screen -->
                <div v-else class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
                    <div class="w-24 h-24 bg-orange-100 text-orange-500 rounded-2xl mx-auto flex items-center justify-center text-4xl mb-6">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <h2 class="text-3xl font-black text-gray-900 mb-2">¡Práctica completada!</h2>
                    <p class="text-gray-500 mb-4">
                        Respondiste correctamente
                        <span class="font-black text-gray-900">{{ correctCount }}</span> de
                        <span class="font-black text-gray-900">{{ total }}</span> preguntas.
                    </p>
                    <div class="inline-flex items-center gap-2 px-6 py-3 bg-orange-50 text-orange-600 rounded-2xl text-xl font-black mb-10">
                        <i class="fa-solid fa-star"></i>
                        +{{ xpEarned }} XP ganados
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            :href="route('dashboard')"
                            class="bg-gray-900 text-white px-8 py-4 rounded-2xl font-black text-lg hover:bg-gray-800 transition-all"
                        >
                            Volver al Panel
                        </Link>
                        <Link
                            :href="route('practice.daily')"
                            class="bg-orange-500 text-white px-8 py-4 rounded-2xl font-black text-lg hover:bg-orange-600 transition-all"
                        >
                            Otra ronda
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.3s ease-out; }
</style>
