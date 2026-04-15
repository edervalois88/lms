<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import QuestionCard from '@/Components/Quiz/QuestionCard.vue';
import FeedbackPanel from '@/Components/Quiz/FeedbackPanel.vue';
import TutorChat from '@/Components/Quiz/TutorChat.vue';
import UpgradeModal from '@/Components/UI/UpgradeModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarTutor from '@/Components/Progress/AvatarTutor.vue';
import AvatarDialog from '@/Components/Progress/AvatarDialog.vue';
import ProgressBar from '@/Components/Progress/ProgressBar.vue';
import RewardFeedback from '@/Components/Progress/RewardFeedback.vue';
import { useGameProgress } from '@/Composables/useGameProgress';
import { useRewardFeedback } from '@/Composables/useRewardFeedback';

const page = usePage();

const props = defineProps({
    subject: { type: Object, required: true },
    topics: { type: Array, required: true },
    quiz: { type: Object, required: true },
    user: { type: Object, required: true },
});

const activeTopic = ref(null);
const currentQuestion = ref(null);
const loading = ref(false);
const showFeedback = ref(false);
const lastAnswerCorrect = ref(false);
const score = ref(0);
const totalAnswered = ref(0);
const requestError = ref('');
const selectedIndex = ref(null);
const adaptiveFeedback = ref(null);
const tutorLoading = ref(false);
const xpToast = ref('');
const xpToastTimer = ref(null);
const showLevelModal = ref(false);
const levelModalData = ref({ level: 1, badges: [] });
const showUpgradeModal = ref(false);
const blockedFeature = ref('ai_tutor');
const isSubmitting = ref(false);

// Gamification state
const currentQuestionIndex = ref(0);
const showAvatarDialog = ref(false);
const avatarState = ref('idle');
const showRewardFeedback = ref(false);
const rewardXP = ref(25);

// Initialize gamification composables
const gameProgress = useGameProgress({ value: props.user }, { value: {} });
const rewardFeedback = useRewardFeedback();

// Computed properties for progress tracking
const progressPercentage = computed(() => {
    if (!props.quiz || !props.quiz.questions) return 0;
    return Math.round((currentQuestionIndex.value / props.quiz.questions.length) * 100);
});

const questionsProgress = computed(() => {
    if (!props.quiz || !props.quiz.questions) return '0/0';
    return `${currentQuestionIndex.value + 1}/${props.quiz.questions.length}`;
});

const fetchQuestion = async (topic) => {
    loading.value = true;
    activeTopic.value = topic;
    requestError.value = '';

    try {
        const response = await axios.post(route('quiz.question', props.subject.slug), {
            topic_id: topic.id,
        }, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        });

        currentQuestion.value = response.data;
    } catch (_error) {
        const serverMessage = _error?.response?.data?.message;
        requestError.value = typeof serverMessage === 'string' && serverMessage !== ''
            ? serverMessage
            : 'No se pudo obtener una pregunta del banco real. Intenta nuevamente.';
        currentQuestion.value = null;
    } finally {
        loading.value = false;
    }
};

const showXpToast = (amount) => {
    xpToast.value = `+${amount} XP`;
    // Clear any previous timer
    if (xpToastTimer.value) clearTimeout(xpToastTimer.value);
    xpToastTimer.value = setTimeout(() => {
        xpToast.value = '';
    }, 1800);
};

const handleAnswer = (selectedIndex) => {
    submitEvaluation(selectedIndex);
};

const selectAnswer = (answerIndex) => {
    submitEvaluation(answerIndex);
};

const submitEvaluation = async (answerIndex) => {
    if (isSubmitting.value || !currentQuestion.value) return;

    isSubmitting.value = true;
    requestError.value = '';

    try {
        const response = await axios.post(route('quiz.evaluate', props.subject.slug), {
            question_id: currentQuestion.value.id,
            selected_index: answerIndex,
            skip_adaptation: false,
        }, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        });

        const payload = response.data;
        adaptiveFeedback.value = payload;
        selectedIndex.value = answerIndex;

        const gamification = payload?.gamification || {};
        if (Number(gamification.xp_earned || 0) > 0) {
            showXpToast(gamification.xp_earned);
        }

        if (Boolean(gamification.level_up)) {
            levelModalData.value = {
                level: Number(gamification.new_level || 1),
                badges: Array.isArray(gamification.unlocked_badges) ? gamification.unlocked_badges : [],
            };
            showLevelModal.value = true;
        }

        const isCorrect = answerIndex === currentQuestion.value.correct_index;
        lastAnswerCorrect.value = isCorrect;

        totalAnswered.value++;
        if (isCorrect) {
            score.value++;
            // Update avatar state and show reward
            avatarState.value = 'celebrating';
            rewardXP.value = 25;
            gameProgress.addXP(25);
            rewardFeedback.showReward(25, 'correct');
            rewardFeedback.playSound('correct');
            showRewardFeedback.value = true;
        } else {
            avatarState.value = 'thinking';
            rewardFeedback.playSound('incorrect');
        }
        showFeedback.value = true;
    } catch (_error) {
        requestError.value = 'No se pudo procesar el feedback adaptativo. Intenta nuevamente.';
    } finally {
        isSubmitting.value = false;
    }
};

onBeforeUnmount(() => {
    if (xpToastTimer.value) clearTimeout(xpToastTimer.value);
});

const onRewardFeedbackComplete = () => {
    showRewardFeedback.value = false;
    nextQuestion();
};

const onAvatarClick = () => {
    showAvatarDialog.value = true;
    avatarState.value = 'encouraging';
};

const onAvatarDialogAction = (action) => {
    showAvatarDialog.value = false;
    // Handle dialog actions (tip, explain, roadmap, joke)
    console.log('Avatar dialog action:', action);
};

const onAvatarDialogClose = () => {
    showAvatarDialog.value = false;
    avatarState.value = 'idle';
};

const nextQuestion = () => {
    showFeedback.value = false;
    adaptiveFeedback.value = null;
    selectedIndex.value = null;

    if (currentQuestionIndex.value < (props.quiz?.questions?.length || 0) - 1) {
        currentQuestionIndex.value++;
        avatarState.value = 'idle';
    } else {
        finishQuiz();
    }
};

const finishQuiz = () => {
    // Route to quiz results page
    window.location.href = route('quiz.results', { quiz: props.quiz?.id });
};

const exitQuiz = () => {
    activeTopic.value = null;
    currentQuestion.value = null;
    showFeedback.value = false;
    adaptiveFeedback.value = null;
    selectedIndex.value = null;
};

const handleTutorAsk = async (message) => {
    if (selectedIndex.value === null) return;

    tutorLoading.value = true;
    requestError.value = '';

    try {
        const response = await axios.post(route('quiz.tutor', props.subject.slug), {
            question_id: currentQuestion.value.id,
            selected_index: selectedIndex.value,
            texto_duda: message,
        }, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        });

        const chat = response.data?.chat || {};
        adaptiveFeedback.value = {
            ...(adaptiveFeedback.value || {}),
            gamification: {
                ...(adaptiveFeedback.value?.gamification || {}),
                ...(response.data?.gamification || {}),
            },
            chat: {
                respuesta_directa: chat.respuesta_directa || '',
                es_fuera_de_contexto: Boolean(chat.es_fuera_de_contexto),
                from_cache: Boolean(chat.from_cache ?? response.data?.from_cache),
                tokens_saved: Number(chat.tokens_saved ?? response.data?.tokens_saved ?? 0),
            },
        };
    } catch (_error) {
        if (_error?.response?.status === 403 && _error?.response?.data?.error === 'freemium_limit_reached') {
            blockedFeature.value = _error?.response?.data?.feature || 'ai_tutor';
            showUpgradeModal.value = true;
            return;
        }

        const serverMessage = _error?.response?.data?.message;
        requestError.value = typeof serverMessage === 'string' && serverMessage !== ''
            ? serverMessage
            : 'No se pudo obtener respuesta del Tutor IA. Intenta nuevamente.';
    } finally {
        tutorLoading.value = false;
    }
};
</script>

<template>
    <Head :title="`Quiz: ${subject?.name || 'Quiz'}`" />

    <AuthenticatedLayout>
        <UpgradeModal :show="showUpgradeModal" :feature="blockedFeature" @close="showUpgradeModal = false" />

        <!-- Reward Feedback Overlay -->
        <RewardFeedback
            v-if="showRewardFeedback && lastAnswerCorrect"
            :xp="rewardXP"
            :show="showRewardFeedback"
            @complete="onRewardFeedbackComplete"
        />

        <!-- Avatar Dialog -->
        <AvatarDialog
            :open="showAvatarDialog"
            context="quiz"
            @action="onAvatarDialogAction"
            @close="onAvatarDialogClose"
        />

        <div class="flex flex-col">

        <div v-if="xpToast" class="fixed top-5 right-5 z-50 rounded-xl bg-emerald-600 text-white px-4 py-2 text-sm font-black shadow-lg animate-fade-in">
            {{ xpToast }}
        </div>

        <div v-if="showLevelModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-2xl">
                <p class="text-xs font-black uppercase tracking-widest text-emerald-600">Nivel alcanzado</p>
                <h3 class="text-3xl font-black text-gray-900 mt-2">Nivel {{ levelModalData.level }}</h3>
                <p class="text-gray-600 mt-2">Tu misión sube de dificultad. Excelente progreso.</p>
                <p v-if="levelModalData.badges.length" class="mt-3 text-sm font-semibold text-orange-600">
                    Insignias: {{ levelModalData.badges.join(', ') }}
                </p>
                <button
                    type="button"
                    @click="showLevelModal = false"
                    class="mt-6 px-6 py-3 rounded-xl bg-gray-900 text-white font-bold hover:bg-black"
                >
                    Continuar
                </button>
            </div>
        </div>

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

                    <div v-else-if="currentQuestion && props.quiz" class="animate-slide-up">
                        <!-- Progress Bar -->
                        <ProgressBar
                            :percentage="progressPercentage"
                            :label="`Pregunta ${questionsProgress}`"
                        />

                        <!-- Quiz Layout with Avatar -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
                            <!-- Left: Avatar Tutor -->
                            <div class="md:col-span-1 flex justify-center md:justify-start">
                                <AvatarTutor
                                    :state="avatarState"
                                    size="lg"
                                    @interaction="onAvatarClick"
                                />
                            </div>

                            <!-- Right: Question Card (3 cols) -->
                            <div class="md:col-span-3">
                                <QuestionCard
                                    :question="currentQuestion"
                                    :time-limit="60"
                                    :disabled="showFeedback || showRewardFeedback || isSubmitting"
                                    @answered="handleAnswer"
                                />

                                <div v-if="showFeedback && !showRewardFeedback" class="mt-6 animate-fade-in">
                                    <FeedbackPanel
                                        :feedback="adaptiveFeedback"
                                        @next="nextQuestion"
                                    />

                                    <div class="mt-4">
                                        <TutorChat
                                            :enabled="showFeedback"
                                            :loading="tutorLoading"
                                            :response="adaptiveFeedback?.chat?.respuesta_directa || ''"
                                            :blocked="Boolean(adaptiveFeedback?.chat?.es_fuera_de_contexto)"
                                            :from-cache="Boolean(adaptiveFeedback?.chat?.from_cache)"
                                            :tokens-saved="Number(adaptiveFeedback?.chat?.tokens_saved || 0)"
                                            :current-xp="Number(adaptiveFeedback?.gamification?.current_xp || 0)"
                                            @ask="handleTutorAsk"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="requestError" class="rounded-2xl border border-rose-300 bg-rose-50 text-rose-700 p-4 font-semibold">
                        <p>{{ requestError }}</p>
                        <button
                            type="button"
                            class="mt-3 inline-flex items-center rounded-xl border border-rose-300 bg-white px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-100 transition-colors"
                            @click="fetchQuestion(activeTopic)"
                        >
                            Reintentar
                        </button>
                    </div>
                </div>

            </div>
        </main>
        </div>
    </AuthenticatedLayout>
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
