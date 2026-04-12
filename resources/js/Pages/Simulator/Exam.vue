<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    exam: Object,
    questions: Array,
});

const currentQuestionIndex = ref(0);
const answers = ref({});
const timeLeft = ref(props.exam.time_limit_minutes * 60);
const timerInterval = ref(null);
const questionStartTime = ref(Date.now());
const visibilityWarnings = ref(0);
const showVisibilityAlert = ref(false);
const hasSubmitted = ref(false);

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value]);
const isSimulation = computed(() => props.exam?.type === 'simulation');
const severeWarning = computed(() => visibilityWarnings.value >= 3);

const formattedTime = computed(() => {
    const h = Math.floor(timeLeft.value / 3600);
    const m = Math.floor((timeLeft.value % 3600) / 60);
    const s = timeLeft.value % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

const startTimer = () => {
    clearInterval(timerInterval.value);
    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            submitExam(true);
        }
    }, 1000);
};

const selectAnswer = (index) => {
    const isCorrect = currentQuestion.value.options[index] === currentQuestion.value.correct_answer;
    const now = Date.now();
    const timeSpent = Math.floor((now - questionStartTime.value) / 1000);
    
    answers.value[currentQuestionIndex.value] = {
        question_id: currentQuestion.value.id,
        selected_index: index,
        is_correct: isCorrect,
        time_spent: timeSpent
    };
};

const nextQuestion = () => {
    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
        questionStartTime.value = Date.now();
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
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

onUnmounted(() => {
    clearInterval(timerInterval.value);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <Head title="Simulacro en Progreso" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">UNAM: {{ exam.type }}</h2>
                <div class="flex items-center gap-6">
                    <div v-if="isSimulation" class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-[11px] font-black uppercase tracking-widest">
                        Modo estricto · sin ayudas
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase font-bold">Tiempo</p>
                        <p class="text-xl font-mono font-black" :class="timeLeft < 300 ? 'text-red-500 animate-pulse' : 'text-gray-900'">
                            {{ formattedTime }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-160px)] overflow-hidden">
            <!-- Sidebar Navigation -->
            <aside class="w-72 bg-white border-r border-gray-200 flex flex-col">
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tu Progreso</p>
                    <p class="text-lg font-black text-gray-900">{{ Object.keys(answers).length }} / {{ questions.length }}</p>
                    <div class="mt-2 w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-orange-500 h-full transition-all" :style="{ width: (Object.keys(answers).length / questions.length * 100) + '%' }"></div>
                    </div>
                </div>
                <div class="grow overflow-y-auto p-4 grid grid-cols-5 gap-2 content-start">
                    <button 
                        v-for="(q, index) in questions" 
                        :key="index"
                        @click="currentQuestionIndex = index; questionStartTime = Date.now();"
                        class="w-10 h-10 rounded-lg text-xs font-bold transition-all border-2"
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
                        class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition-colors"
                    >
                        Finalizar Examen
                    </button>
                </div>
            </aside>

            <!-- Question Area -->
            <main class="grow overflow-y-auto bg-gray-50 p-6 md:p-12 lg:p-20">
                <div v-if="currentQuestion" class="max-w-3xl mx-auto space-y-10">
                    
                    <div class="bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-6">
                            <span class="px-2 py-1 bg-orange-100 text-orange-600 rounded text-[10px] font-black uppercase">Pregunta {{ currentQuestionIndex + 1 }}</span>
                            <span class="text-gray-300">|</span>
                            <span class="text-gray-400 text-xs font-bold">{{ currentQuestion.subject_name || 'General' }}</span>
                        </div>
                        
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight mb-12">
                            {{ currentQuestion.stem }}
                        </h2>

                        <div class="grid grid-cols-1 gap-4">
                            <button
                                v-for="(option, idx) in currentQuestion.options"
                                :key="idx"
                                @click="selectAnswer(idx)"
                                class="flex items-center p-5 bg-white rounded-2xl border-2 transition-all text-left group"
                                :class="answers[currentQuestionIndex]?.selected_index === idx ? 'border-orange-500 bg-orange-50' : 'border-gray-100 hover:border-orange-200'"
                            >
                                <span 
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-bold mr-6 transition-colors"
                                    :class="answers[currentQuestionIndex]?.selected_index === idx ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'"
                                >
                                    {{ String.fromCharCode(65 + idx) }}
                                </span>
                                <span class="text-lg font-medium text-gray-700">{{ option }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button 
                            @click="prevQuestion"
                            :disabled="currentQuestionIndex === 0"
                            class="flex items-center gap-2 text-gray-500 font-bold hover:text-gray-900 disabled:opacity-30"
                        >
                            <i class="fa-solid fa-arrow-left"></i> Anterior
                        </button>
                        
                        <button 
                            v-if="currentQuestionIndex < questions.length - 1"
                            @click="nextQuestion"
                            class="bg-white text-gray-900 px-8 py-3 rounded-xl font-bold border border-gray-200 shadow-sm hover:shadow-md transition-all"
                        >
                            Siguiente
                        </button>
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
                        class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-400 text-white font-black text-sm uppercase tracking-wider"
                        @click="showVisibilityAlert = false"
                    >
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
