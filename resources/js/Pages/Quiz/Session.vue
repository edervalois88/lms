<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import QuestionCard from '@/Components/Quiz/QuestionCard.vue';
import AnswerFeedback from '@/Components/Quiz/AnswerFeedback.vue';

const props = defineProps({
    subject: Object,
    topics: Array,
});

const activeTopic = ref(null);
const currentQuestion = ref(null);
const loading = ref(false);
const showFeedback = ref(false);
const lastAnswerCorrect = ref(false);
const score = ref(0);
const totalAnswered = ref(0);
const requestError = ref('');

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const fetchQuestion = async (topic) => {
    loading.value = true;
    activeTopic.value = topic;
    requestError.value = '';

    try {
        const response = await fetch(route('quiz.question', props.subject.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ topic_id: topic.id }),
        });

        if (!response.ok) {
            throw new Error('No se pudo cargar una pregunta real.');
        }

        currentQuestion.value = await response.json();
    } catch (_error) {
        requestError.value = 'No se pudo obtener una pregunta del banco real. Intenta nuevamente.';
        currentQuestion.value = null;
    } finally {
        loading.value = false;
    }
};

const handleAnswer = (selectedIndex) => {
    lastAnswerCorrect.value = selectedIndex === currentQuestion.value.correct_index;
    if (lastAnswerCorrect.value) score.value++;
    totalAnswered.value++;
    showFeedback.value = true;
};

const nextQuestion = () => {
    showFeedback.value = false;
    fetchQuestion(activeTopic.value);
};

const exitQuiz = () => {
    activeTopic.value = null;
    currentQuestion.value = null;
    showFeedback.value = false;
};
</script>

<template>
    <Head :title="`Quiz: ${subject.name}`" />

    <div class="min-h-screen bg-gray-50 flex flex-col">
        <!-- Header -->
        <nav class="bg-white border-b border-gray-200 px-4 py-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('quiz.index')" class="text-gray-400 hover:text-gray-600 mr-4">
                        <i class="fa-solid fa-chevron-left text-xl"></i>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ subject.name }}</h1>
                        <p class="text-sm text-gray-500">Sesión de estudio adaptativa</p>
                    </div>
                </div>
                <div v-if="activeTopic" class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Aciertos</p>
                        <p class="text-lg font-black text-orange-600">{{ score }}/{{ totalAnswered }}</p>
                    </div>
                    <button @click="exitQuiz" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                        Finalizar
                    </button>
                </div>
            </div>
        </nav>

            <main class="grow flex items-center justify-center p-4">
            <div class="max-w-4xl w-full">
                
                <!-- Topic Selection -->
                <div v-if="!activeTopic" class="animate-fade-in">
                    <div class="text-center mb-12">
                        <div class="inline-flex p-4 rounded-2xl mb-4" :style="{ backgroundColor: subject.color + '20', color: subject.color }">
                            <i :class="`fa-solid fa-${subject.icon} text-4xl`"></i>
                        </div>
                        <h2 class="text-3xl font-extrabold text-gray-900">¿Qué desea repasar hoy?</h2>
                        <p class="text-gray-500 mt-2">Selecciona un tema para generar preguntas personalizadas.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <button 
                            v-for="topic in topics" 
                            :key="topic.id"
                            @click="fetchQuestion(topic)"
                            class="flex items-center p-6 bg-white border-2 border-transparent hover:border-orange-500 rounded-2xl shadow-sm transition-all text-left group"
                        >
                            <div class="grow">
                                <h3 class="font-bold text-lg text-gray-900 group-hover:text-orange-600 transition-colors">{{ topic.name }}</h3>
                                <div class="flex items-center mt-2 text-sm text-gray-400 font-medium">
                                    <span class="mr-3 flex items-center">
                                        <i class="fa-solid fa-layer-group mr-1.5 text-xs"></i>
                                        Nivel {{ topic.difficulty_base || 3 }}
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fa-solid fa-circle-question mr-1.5 text-xs"></i>
                                        {{ topic.questions_count || 10 }}+ preguntas
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4 w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-300 group-hover:bg-orange-50 group-hover:text-orange-600 transition-all">
                                <i class="fa-solid fa-play text-xs"></i>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Quiz Interface -->
                <div v-else class="space-y-6">
                    <div v-if="loading" class="text-center py-20">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent mb-4"></div>
                        <p class="text-lg font-medium text-gray-600">Nuestra IA está diseñando tu pregunta...</p>
                    </div>

                    <div v-else-if="currentQuestion" class="animate-slide-up">
                        <QuestionCard 
                            :question="currentQuestion" 
                            :time-limit="60"
                            :disabled="showFeedback"
                            @answered="handleAnswer"
                        />
                        
                        <div v-if="showFeedback" class="mt-6 animate-fade-in">
                            <AnswerFeedback 
                                :correct="lastAnswerCorrect"
                                :explanation="currentQuestion.explanation"
                                :concept="currentQuestion.concept"
                                :topic-detail="currentQuestion.topic_detail"
                                @next="nextQuestion"
                            />
                        </div>
                    </div>

                    <div v-else-if="requestError" class="rounded-2xl border border-rose-300 bg-rose-50 text-rose-700 p-4 font-semibold">
                        {{ requestError }}
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slide-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.5s ease-out; }
.animate-slide-up { animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
</style>
