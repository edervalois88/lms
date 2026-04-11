<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { animate, spring, stagger } from 'motion';
import { playSound } from '@/Utils/SoundService';

const props = defineProps({
    exam: Object,
    correct: Number,
    total: Number,
    percentage: Number,
    message: String,
    goal: Object,
    ai_suggestions: Array
});

const scoreCategory = computed(() => {
    if (props.percentage >= 80) return { color: 'text-green-500', bg: 'bg-green-50', icon: 'fa-solid fa-award', sound: 'success' };
    if (props.percentage >= 60) return { color: 'text-blue-500', bg: 'bg-blue-50', icon: 'fa-solid fa-chart-line', sound: 'success' };
    if (props.percentage >= 40) return { color: 'text-orange-500', bg: 'bg-orange-50', icon: 'fa-solid fa-fire', sound: 'pop' };
    return { color: 'text-red-500', bg: 'bg-red-50', icon: 'fa-solid fa-book-open', sound: 'error' };
});

const isGoalReached = computed(() => props.goal && props.correct >= props.goal.min_score);
const gapToGoal = computed(() => props.goal ? (props.goal.min_score - props.correct) : null);

const timeTaken = computed(() => {
    if (!props.exam.started_at || !props.exam.completed_at) return '00:00:00';
    
    const start = new Date(props.exam.started_at);
    const end = new Date(props.exam.completed_at);
    const diff = Math.abs(end - start) / 1000; 

    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = Math.floor(diff % 60);

    return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
});

onMounted(() => {
    playSound(scoreCategory.value.sound);
    
    animate(".result-header", { opacity: [0, 1], scale: [0.8, 1], rotate: [-5, 0] }, { duration: 0.8, easing: spring() });
    animate(".stat-pill", { opacity: [0, 1], y: [20, 0] }, { delay: stagger(0.1), duration: 0.5, easing: spring() });
    
    if (props.ai_suggestions.length > 0) {
        animate(".ai-card", { opacity: [0, 1], x: [50, 0] }, { delay: 1, duration: 0.8, easing: spring() });
        animate(".suggestion-item", { opacity: [0, 1], scale: [0.95, 1] }, { delay: stagger(0.1, { start: 1.2 }), duration: 0.4 });
    }
});
</script>

<template>
    <Head title="Resultados del Simulacro - NexusEdu" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 flex flex-col items-center gap-8">
        <div class="max-w-3xl w-full space-y-8">
            
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 result-header">
                
                <!-- Confetti/Celebration Header -->
                <div class="p-10 text-center relative overflow-hidden" :class="scoreCategory.bg">
                    <div 
                        class="w-20 h-20 rounded-2xl mx-auto flex items-center justify-center mb-6 text-3xl shadow-lg transform score-icon"
                        :class="scoreCategory.color.replace('text', 'bg').replace('500', '600') + ' text-white'"
                    >
                        <i :class="scoreCategory.icon"></i>
                    </div>
                    <h1 class="text-3xl font-black text-gray-900 mb-2">¡Simulacro Completado!</h1>
                    <p class="text-lg font-bold" :class="scoreCategory.color">{{ message }}</p>

                    <!-- Particle Decoration -->
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-white/20 rounded-full blur-xl"></div>
                    <div class="absolute bottom-4 right-4 w-20 h-20 bg-white/20 rounded-full blur-2xl"></div>
                </div>

                <div class="p-10 space-y-10">
                    
                    <!-- Goal Analysis Section -->
                    <div v-if="goal" class="bg-gray-50 p-8 rounded-3xl border-2 border-dashed border-gray-100 stat-pill">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="text-center md:text-left">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tu Meta en UNAM</p>
                                <h2 class="text-2xl font-black text-gray-900">{{ goal.name }}</h2>
                                <p class="text-sm text-gray-400 font-medium">{{ goal.school_name }}</p>
                            </div>
                            <div class="text-center bg-white px-6 py-4 rounded-2xl shadow-sm border border-gray-100 transform hover:rotate-3 transition-transform">
                                <p class="text-3xl font-black" :class="isGoalReached ? 'text-green-500' : 'text-orange-500'">
                                    {{ correct }} <span class="text-sm text-gray-400 font-bold">/ {{ goal.min_score }}</span>
                                </p>
                                <p class="text-[10px] uppercase font-black tracking-widest text-gray-400 mt-1">Aciertos vs Meta</p>
                            </div>
                        </div>

                        <div class="mt-8">
                            <div v-if="isGoalReached" class="bg-green-100 text-green-700 p-4 rounded-xl flex items-center gap-3 animate-pulse shadow-sm">
                                <i class="fa-solid fa-check-circle text-xl"></i>
                                <p class="font-bold">¡Lo lograste! Con este puntaje entrarías a tu carrera objetivo.</p>
                            </div>
                            <div v-else class="bg-orange-100 text-orange-700 p-4 rounded-xl flex items-center gap-3 shadow-sm">
                                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                                <p class="font-bold">Te faltan <span class="text-2xl underline">{{ gapToGoal }}</span> aciertos para alcanzar el mínimo histórico.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 text-center stat-pill">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Precisión</p>
                            <p class="text-3xl font-black text-gray-900">{{ percentage }}%</p>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 text-center stat-pill">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tiempo</p>
                            <p class="text-3xl font-black text-gray-900">{{ timeTaken }}</p> 
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 stat-pill">
                        <Link 
                            :href="route('dashboard')"
                            @click="playSound('click')"
                            class="flex-grow order-2 sm:order-1 bg-gray-100 text-gray-700 px-8 py-4 rounded-2xl font-black text-lg hover:bg-gray-200 transition-colors flex items-center justify-center"
                        >
                            Ir al Panel
                        </Link>
                        <Link 
                            :href="route('simulator.index')"
                            @click="playSound('success')"
                            class="flex-grow order-1 sm:order-2 bg-orange-500 text-white px-8 py-4 rounded-2xl font-black text-lg hover:bg-orange-600 transition-all shadow-lg transform hover:scale-105 active:scale-95 flex items-center justify-center"
                        >
                            Intentar de Nuevo
                            <i class="fa-solid fa-redo ml-3 text-sm"></i>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- AI Alternative Careers -->
            <div v-if="!isGoalReached && ai_suggestions.length > 0" class="bg-gray-900 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden ai-card">
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <h3 class="text-xl font-black tracking-tight">Opciones estratégicas de la IA</h3>
                    </div>
                    
                    <p class="text-gray-400 mb-8 font-medium">Dado que tu puntaje objetivo es alto, Claude sugiere estas carreras alternativas con planes de estudio similares en la UNAM:</p>
                    
                    <div class="grid gap-4">
                        <div 
                            v-for="suggest in ai_suggestions" 
                            :key="suggest.name"
                            class="p-5 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 transition-colors suggestion-item"
                        >
                            <p class="font-black text-orange-400 mb-1">{{ suggest.name }}</p>
                            <p class="text-sm text-gray-400">{{ suggest.reason }}</p>
                        </div>
                    </div>
                </div>
                <!-- Abstract BG -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/10 rounded-full blur-3xl"></div>
            </div>

        </div>
    </div>
</template>

<style>
.score-icon {
    animation: bounce 3s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0) rotate(12deg); }
    50% { transform: translateY(-10px) rotate(15deg); }
}
</style>
