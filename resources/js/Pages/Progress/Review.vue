<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuestionCard from '@/Components/Quiz/QuestionCard.vue';
import AnswerFeedback from '@/Components/Quiz/AnswerFeedback.vue';
import { ref } from 'vue';

const props = defineProps({
    due_cards: Array,
    total_due: Number,
});

const currentIndex = ref(0);
const currentCard = ref(props.due_cards[0] || null);
const showFeedback = ref(false);
const isCorrect = ref(false);
const submitting = ref(false);
const apiError = ref('');

// CSRF token configurado globalmente en axios
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const handleAnswer = async (selectedIndex) => {
    if (submitting.value || !currentCard.value) return;

    apiError.value = '';
    submitting.value = true;
    const answerIsCorrect = selectedIndex === currentCard.value.question.correct_index;

    // SM-2 simplified quality: correct answer = 5, wrong = 1
    const quality = answerIsCorrect ? 5 : 1;

    try {
        const response = await fetch(route('review.answer', currentCard.value.question.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                
                'Accept': 'application/json',
            },
            body: JSON.stringify({ quality, source: 'review' }),
        });

        if (!response.ok) {
            throw new Error('No se pudo guardar la respuesta.');
        }

        isCorrect.value = answerIsCorrect;
        showFeedback.value = true;
    } catch (_error) {
        apiError.value = 'No pudimos guardar tu respuesta. Reintenta para continuar.';
    } finally {
        submitting.value = false;
    }
};

const nextCard = () => {
    showFeedback.value = false;
    currentIndex.value++;
    if (currentIndex.value < props.due_cards.length) {
        currentCard.value = props.due_cards[currentIndex.value];
    } else {
        currentCard.value = null;
    }
};
</script>

<template>
    <Head title="Repaso Inteligente - NexusEdu" />
    
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Repaso del día</h2>
                <div class="px-4 py-1 bg-orange-100 text-orange-600 rounded-full text-xs font-black uppercase">
                    {{ total_due }} pendientes
                </div>
            </div>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-3xl mx-auto">
                
                <div v-if="currentCard" class="space-y-6">
                    <div
                        v-if="apiError"
                        class="rounded-2xl border border-rose-500/40 bg-rose-500/10 text-rose-300 px-4 py-3 text-sm font-bold"
                    >
                        {{ apiError }}
                    </div>

                    <div class="flex items-center justify-between px-2">
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">
                            Sesión: {{ currentIndex + 1 }} / {{ due_cards.length }}
                        </p>
                        <p class="text-xs font-bold text-gray-400 italic">Materia: {{ currentCard.question.topic.subject.name }}</p>
                    </div>

                    <QuestionCard 
                        :question="currentCard.question" 
                        :disabled="showFeedback"
                        @answered="handleAnswer"
                    />

                    <div v-if="showFeedback" class="animate-fade-in">
                        <AnswerFeedback 
                            :correct="isCorrect"
                            :explanation="currentCard.question.explanation"
                            :concept="currentCard.question.topic.name"
                            :topic-detail="currentCard.question.topic?.description"
                            @next="nextCard"
                        />
                    </div>
                </div>

                <div v-else class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-6">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h2 class="text-3xl font-black text-gray-900 mb-2">¡Todo al día!</h2>
                    <p class="text-gray-500 mb-10">Has completado todos tus repasos programados por hoy.</p>
                    <Link 
                        :href="route('dashboard')"
                        class="bg-gray-900 text-white px-10 py-4 rounded-2xl font-black text-lg hover:bg-gray-800 transition-all"
                    >
                        Volver al Panel
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
