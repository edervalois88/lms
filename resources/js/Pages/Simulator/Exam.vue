<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarTutor from '@/Components/Progress/AvatarTutor.vue';
import AvatarDialog from '@/Components/Progress/AvatarDialog.vue';
import ProgressBar from '@/Components/Progress/ProgressBar.vue';
import RewardFeedback from '@/Components/Progress/RewardFeedback.vue';
import { useGameProgress } from '@/Composables/useGameProgress';
import { useRewardFeedback } from '@/Composables/useRewardFeedback';

const page = usePage();

const props = defineProps({
    exam: Object,
    questions: Array,
    user: Object,
});

// Exam state
const currentQuestionIndex = ref(0);
const answers = ref({});
const timeRemaining = ref((props.exam?.time_limit_minutes || 60) * 60);
const timerInterval = ref(null);
const questionStartTime = ref(Date.now());
const visibilityWarnings = ref(0);
const showVisibilityAlert = ref(false);
const hasSubmitted = ref(false);

// Gamification state
const selectedAnswer = ref(null);
const showAvatarDialog = ref(false);
const showRewardFeedback = ref(false);
const rewardXp = ref(50); // Simulator awards 50 XP
const avatarState = ref('idle');
const correctAnswers = ref(0);
const xpToastTimer = ref(null);
const isSubmitting = ref(false);

// Initialize composables
const gameProgress = useGameProgress(page.props.user);
const { showReward, playSound, getRewardMessage } = useRewardFeedback();

const currentQuestion = computed(() => props.questions?.[currentQuestionIndex.value]);
const isSimulation = computed(() => props.exam?.type === 'simulation');
const severeWarning = computed(() => visibilityWarnings.value >= 3);

// Gamification computed properties
const progressPercentage = computed(() => {
    if (!props.questions?.length) return 0;
    return Math.round((currentQuestionIndex.value / props.questions.length) * 100);
});

const questionsProgress = computed(() => {
    if (!props.questions?.length) return '0/0';
    return `${currentQuestionIndex.value + 1}/${props.questions.length}`;
});

// Live score prediction: 0-20 scale based on current accuracy
const scorePrediction = computed(() => {
    if (currentQuestionIndex.value === 0) return 0;
    const percentage = (correctAnswers.value / currentQuestionIndex.value) * 100;
    // Map 0-100% to 0-20 scale
    return Math.round((percentage / 100) * 20);
});

// Format time as HH:MM:SS
const formattedTime = computed(() => {
    const h = Math.floor(timeRemaining.value / 3600);
    const m = Math.floor((timeRemaining.value % 3600) / 60);
    const s = timeRemaining.value % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

const startTimer = () => {
    clearInterval(timerInterval.value);
    timerInterval.value = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;
        } else {
            finishExam();
        }
    }, 1000);
};

const isCorrect = (answer) => {
    return answer === currentQuestion.value?.correct_answer;
};

const onAvatarClick = () => {
    showAvatarDialog.value = true;
};

const onAvatarDialogAction = (action) => {
    if (action === 'tip') {
        const message = getRewardMessage('simulator', 'motivation');
    } else if (action === 'explain') {
        // Call AI tutor with exam context
    } else if (action === 'roadmap') {
        // Show weaknesses based on performance
    } else if (action === 'joke') {
        // Motivational message
    }
    showAvatarDialog.value = false;
};

const onRewardFeedbackComplete = () => {
    showRewardFeedback.value = false;
};

const finishExam = () => {
    if (timerInterval.value) clearInterval(timerInterval.value);
    if (xpToastTimer.value) clearTimeout(xpToastTimer.value);

    const finalScore = scorePrediction.value;
    // Navigate to results page
    window.location.href = route('simulator.results', {
        examId: props.exam.id,
        score: finalScore,
        correctAnswers: correctAnswers.value,
        totalQuestions: props.questions?.length || 0
    });
};

const selectAnswer = async (answer) => {
    if (isSubmitting.value || !currentQuestion.value) return;
    isSubmitting.value = true;

    try {
        selectedAnswer.value = answer;
        const now = Date.now();
        const timeSpent = Math.floor((now - questionStartTime.value) / 1000);

        answers.value[currentQuestionIndex.value] = {
            question_id: currentQuestion.value.id,
            selected_answer: answer,
            is_correct: isCorrect(answer),
            time_spent: timeSpent
        };

        if (isCorrect(answer)) {
            correctAnswers.value++;
            avatarState.value = 'celebrating';
            gameProgress.addXP(50);
            showReward(50, 'simulator');
            playSound('correct');
            showRewardFeedback.value = true;

            // Auto-advance after reward animation
            xpToastTimer.value = setTimeout(() => {
                nextQuestion();
            }, 1500);
        } else {
            avatarState.value = 'thinking';
            playSound('incorrect');

            xpToastTimer.value = setTimeout(() => {
                avatarState.value = 'encouraging';
            }, 2000);
        }
    } finally {
        isSubmitting.value = false;
    }
};

const nextQuestion = () => {
    if (xpToastTimer.value) clearTimeout(xpToastTimer.value);

    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
        selectedAnswer.value = null;
        avatarState.value = 'idle';
        showRewardFeedback.value = false;
        questionStartTime.value = Date.now();
    } else {
        finishExam();
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
        selectedAnswer.value = null;
        avatarState.value = 'idle';
        showRewardFeedback.value = false;
        questionStartTime.value = Date.now();
    }
};

const form = useForm({
    answers: []
});

const submitExam = (forceAuto = false) => {
    if (hasSubmitted.value || form.processing) {
        return;
    }

    if (!forceAuto && Object.keys(answers.value).length < props.questions.length) {
        if (!confirm('Aún tienes preguntas sin responder. ¿Estás seguro de que deseas finalizar?')) {
            return;
        }
    }

    hasSubmitted.value = true;
    form.answers = Object.values(answers.value);
    form.post(route('simulator.submit', props.exam.id), {
        onError: () => {
            hasSubmitted.value = false;
        },
    });
};

const handleVisibilityChange = () => {
    if (!isSimulation.value) {
        return;
    }

    if (document.hidden) {
        visibilityWarnings.value++;
        showVisibilityAlert.value = true;
    }
};

onMounted(() => {
    startTimer();
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
    if (timerInterval.value) clearInterval(timerInterval.value);
    if (xpToastTimer.value) clearTimeout(xpToastTimer.value);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <Head title="Simulacro en Progreso" />
    <AuthenticatedLayout>
        <!-- Reward Feedback Overlay -->
        <RewardFeedback
            v-if="showRewardFeedback"
            :xp="rewardXp"
            :show="showRewardFeedback"
            @complete="onRewardFeedbackComplete"
        />

        <!-- Avatar Dialog -->
        <AvatarDialog
            :open="showAvatarDialog"
            context="simulator"
            @action="onAvatarDialogAction"
            @close="showAvatarDialog = false"
        />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">UNAM: {{ exam.type }}</h2>
                <div class="flex items-center gap-6">
                    <div v-if="isSimulation" class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-[11px] font-black uppercase tracking-widest">
                        Modo estricto · sin ayudas
                    </div>
                    <div class="exam-timer text-right">
                        <p class="text-xs text-gray-400 uppercase font-bold">Tiempo</p>
                        <p class="text-xl font-mono font-black" :class="timeRemaining < 300 ? 'text-red-500 animate-pulse' : 'text-gray-900'">
                            {{ formattedTime }}
                        </p>
                    </div>
                    <div class="score-prediction text-right">
                        <p class="text-xs text-gray-400 uppercase font-bold">Predicción</p>
                        <p class="text-xl font-mono font-black text-purple-600">{{ scorePrediction }}/20</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col md:flex-row h-auto md:h-[calc(100vh-160px)] overflow-hidden">
            <!-- Sidebar Navigation -->
            <aside v-if="!isSimulation" class="w-full md:w-72 bg-white border-b md:border-b-0 md:border-r border-gray-200 flex flex-col">
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tu Progreso</p>
                    <p class="text-lg font-black text-gray-900">{{ Object.keys(answers).length }} / {{ props.questions?.length || 0 }}</p>
                    <div class="mt-2 w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-orange-500 h-full transition-all" :style="{ width: (Object.keys(answers).length / (props.questions?.length || 1) * 100) + '%' }"></div>
                    </div>
                </div>
                <div class="grow overflow-y-auto p-4 grid grid-cols-6 sm:grid-cols-8 md:grid-cols-5 gap-2 content-start">
                    <button
                        v-for="(q, index) in props.questions"
                        :key="index"
                        @click="currentQuestionIndex = index; questionStartTime = Date.now();"
                        class="w-10 h-10 min-h-11 rounded-lg text-xs font-bold transition-all border-2"
                        :class="[
                            currentQuestionIndex === index ? 'border-orange-500 bg-orange-50 text-orange-600' :
                            answers[index] ? 'border-green-500 bg-green-500 text-white' : 'border-gray-100 bg-white text-gray-400'
                        ]"
                    >
                        {{ index + 1 }}
                    </button>
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100">
                    <button
                        @click="submitExam(false)"
                        class="w-full bg-gray-900 text-white py-3 min-h-11 rounded-xl font-bold hover:bg-gray-800 transition-colors"
                    >
                        Finalizar Examen
                    </button>
                </div>
            </aside>

            <!-- Question Area with Gamification -->
            <main class="grow overflow-y-auto bg-gray-50 p-6 md:p-12 lg:p-20">
                <div v-if="currentQuestion" class="max-w-4xl mx-auto">
                    <!-- Progress Bar -->
                    <ProgressBar
                        :percentage="progressPercentage"
                        :label="`Pregunta ${questionsProgress}`"
                        height="h-3"
                        class="mb-8"
                    />

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 space-y-10 md:space-y-0">
                        <!-- Avatar (Left) -->
                        <div class="md:col-span-1 flex justify-center">
                            <AvatarTutor
                                :state="avatarState"
                                size="lg"
                                :serious="true"
                                @interaction="onAvatarClick"
                            />
                        </div>

                        <!-- Question Content (Right) -->
                        <div class="md:col-span-3">
                            <div class="bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-gray-100">
                                <div class="flex items-center gap-2 mb-6">
                                    <span class="px-2 py-1 bg-orange-100 text-orange-600 rounded text-[10px] font-black uppercase">Pregunta {{ currentQuestionIndex + 1 }}</span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-gray-400 text-xs font-bold">{{ currentQuestion.subject_name || 'General' }}</span>
                                </div>

                                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight mb-12">
                                    {{ currentQuestion.stem || currentQuestion.question }}
                                </h2>

                                <div class="grid grid-cols-1 gap-4">
                                    <button
                                        v-for="(option, idx) in currentQuestion.options"
                                        :key="idx"
                                        @click="selectAnswer(option)"
                                        :disabled="selectedAnswer !== null || isSubmitting"
                                        class="flex items-center p-5 bg-white rounded-2xl border-2 transition-all text-left group disabled:opacity-50"
                                        :class="[
                                            selectedAnswer === null
                                                ? 'border-gray-100 hover:border-orange-200 cursor-pointer'
                                                : selectedAnswer === option
                                                    ? isCorrect(option)
                                                        ? 'border-green-500 bg-green-50'
                                                        : 'border-red-500 bg-red-50'
                                                    : 'border-gray-100'
                                        ]"
                                    >
                                        <span
                                            class="w-10 h-10 rounded-xl flex items-center justify-center font-bold mr-6 transition-colors"
                                            :class="[
                                                selectedAnswer === option
                                                    ? isCorrect(option)
                                                        ? 'bg-green-500 text-white'
                                                        : 'bg-red-500 text-white'
                                                    : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'
                                            ]"
                                        >
                                            {{ String.fromCharCode(65 + idx) }}
                                        </span>
                                        <span class="text-lg font-medium text-gray-700">{{ option }}</span>
                                    </button>
                                </div>

                                <!-- Navigation for incorrect answers -->
                                <div v-if="selectedAnswer !== null && !isCorrect(selectedAnswer)" class="mt-6 flex gap-3">
                                    <button
                                        @click="selectedAnswer = null"
                                        class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold transition"
                                    >
                                        Intentar de nuevo
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-6">
                                <button
                                    @click="prevQuestion"
                                    :disabled="currentQuestionIndex === 0"
                                    class="flex items-center gap-2 text-gray-500 font-bold min-h-11 hover:text-gray-900 disabled:opacity-30"
                                >
                                    <i class="fa-solid fa-arrow-left"></i> Anterior
                                </button>

                                <button
                                    v-if="currentQuestionIndex < (props.questions?.length || 0) - 1 && selectedAnswer === null"
                                    @click="nextQuestion"
                                    class="bg-white text-gray-900 px-8 py-3 min-h-11 rounded-xl font-bold border border-gray-200 shadow-sm hover:shadow-md transition-all"
                                >
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <div v-if="showVisibilityAlert" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl border border-red-500/50 bg-red-950/90 p-6 text-red-100 shadow-2xl">
                <p class="text-xs font-black uppercase tracking-widest text-red-300">Advertencia de foco</p>
                <h3 class="mt-2 text-2xl font-black">¡Atención! En el examen real no puedes distraerte.</h3>
                <p class="mt-3 text-sm text-red-200">Mantén el foco aquí. Cambiar de pestaña reduce la calidad de tu simulación.</p>
                <p class="mt-3 text-sm font-bold">
                    Advertencias: {{ visibilityWarnings }}
                    <span v-if="severeWarning" class="text-yellow-300"> · Modo severo activado. Tu rendimiento será marcado como baja concentración.</span>
                </p>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="px-4 py-2 min-h-11 rounded-lg bg-red-500 hover:bg-red-400 text-white font-black text-sm uppercase tracking-wider"
                        @click="showVisibilityAlert = false"
                    >
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

