<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, onMounted } from 'vue';
import { animate, spring, stagger } from 'motion';
import { playSound } from '@/Utils/SoundService';

const props = defineProps({
    stats: Object,
    recent_exams: Array,
    subject_mastery: Array,
});

const progressPercentage = computed(() => {
    if (!props.stats.projection.projected_score || !authMajor.value) return 0;
    return Math.min(100, Math.round((props.stats.projection.projected_score / authMajor.value.min_score) * 100));
});

const authMajor = computed(() => props.stats.projection.goal_name !== 'No definida' ? {
    name: props.stats.projection.goal_name,
    min_score: props.stats.projection.projected_score + (props.stats.projection.gap_to_goal || 0)
} : null);

const gapStatus = computed(() => {
    const gap = props.stats.projection.gap_to_goal;
    if (gap === null) return { text: 'Configura tu meta', color: 'text-gray-400', bg: 'bg-gray-100' };
    if (gap <= 0) return { text: '¡En rango de ingreso!', color: 'text-green-600', bg: 'bg-green-100' };
    if (gap <= 10) return { text: 'Muy cerca del objetivo', color: 'text-orange-500', bg: 'bg-orange-100' };
    return { text: 'Esfuerzo necesario', color: 'text-red-500', bg: 'bg-red-100' };
});

onMounted(() => {
    // Animación de entrada de los elementos
    animate(".header-section", { opacity: [0, 1], x: [-20, 0] }, { duration: 0.6, easing: spring() });
    animate(".goal-card", { opacity: [0, 1], scale: [0.9, 1] }, { duration: 0.8, easing: spring() });
    animate(".stat-card", { opacity: [0, 1], y: [20, 0] }, { 
        delay: stagger(0.1),
        duration: 0.5,
        easing: spring() 
    });

    // Animación de la barra de progreso
    setTimeout(() => {
        animate(".progress-fill", { width: [0, progressPercentage.value + '%'] }, { duration: 1.5, easing: spring({ stiffness: 100, damping: 10, mass: 1 }) });
        playSound('pop');
    }, 500);
});
</script>

<template>
    <Head title="Panel de Control - NexusEdu" />

    <AuthenticatedLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- Welcome Section -->
                <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 header-section">
                    <div>
                        <h1 class="text-3xl font-black text-gray-900 leading-tight">
                            Hola, <span class="text-orange-600">{{ $page.props.auth.user.name.split(' ')[0] }}</span> 👋
                        </h1>
                        <p class="text-gray-500 font-medium mt-1">Tu camino a la UNAM está en marcha.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 flex items-center transform hover:scale-105 transition-transform cursor-pointer" @click="playSound('click')">
                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fa-solid fa-fire text-orange-500"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Racha</p>
                                <p class="text-lg font-black text-gray-900">{{ stats.streak }} días</p>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Goal Tracker Widget -->
                    <div class="lg:col-span-2 bg-gray-900 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden flex flex-col justify-between goal-card border border-white/5">
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <p class="text-xs font-bold text-orange-400 uppercase tracking-widest mb-1">Tu Objetivo Principal</p>
                                    <h2 class="text-3xl font-black">{{ authMajor?.name || 'No has seleccionado carrera' }}</h2>
                                </div>
                                <div :class="['px-4 py-2 rounded-xl text-xs font-black uppercase tracking-tighter shadow-lg transform hover:rotate-2 transition-transform', gapStatus.bg, gapStatus.color]">
                                    {{ gapStatus.text }}
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="flex justify-between items-end">
                                    <div>
                                        <p class="text-4xl font-black text-orange-500">{{ stats.projection.projected_score }} <span class="text-lg text-white/50">aciertos</span></p>
                                        <p class="text-xs font-bold text-gray-400">Puntaje Proyectado (IA)</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl font-black">{{ authMajor?.min_score || '--' }}</p>
                                        <p class="text-xs font-bold text-gray-400">Meta Histórica</p>
                                    </div>
                                </div>

                                <div class="w-full bg-white/10 h-4 rounded-full overflow-hidden border border-white/5 p-[2px]">
                                    <div 
                                        class="bg-gradient-to-r from-orange-500 to-red-500 h-full rounded-full transition-all shadow-[0_0_20px_rgba(249,115,22,0.4)] progress-fill"
                                        style="width: 0%"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    <span>Comienzo</span>
                                    <span>{{ progressPercentage }}% del objetivo alcanzado</span>
                                    <span>Éxito</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recommendations / IA Tip -->
                        <div class="mt-12 p-5 bg-white/5 border border-white/10 rounded-2xl relative z-10 backdrop-blur-sm group cursor-pointer hover:bg-white/10 transition-colors">
                            <p class="text-sm font-medium leading-relaxed italic text-gray-300">
                                <i class="fa-solid fa-robot text-orange-400 mr-2 group-hover:animate-bounce inline-block"></i>
                                "Según tus últimas respuestas, estás a solo <span class="text-orange-400 font-bold">{{ stats.projection.gap_to_goal }} aciertos</span> de asegurar tu lugar. Refuerza <b>Matemáticas</b> esta semana para cerrar la brecha."
                            </p>
                        </div>

                        <!-- BG Decoration -->
                        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-orange-500/10 rounded-full blur-3xl"></div>
                    </div>

                    <!-- Side Stats -->
                    <div class="space-y-8">
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 stat-card">
                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Precisión Global</h3>
                            <div class="flex items-center justify-center relative">
                                <div class="text-center">
                                    <p class="text-4xl font-black text-gray-900">{{ stats.accuracy }}%</p>
                                    <p class="text-xs font-bold text-green-500 mt-1"><i class="fa-solid fa-arrow-up"></i> +5% este mes</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 stat-card">
                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Materia más fuerte</h3>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl shadow-inner">
                                    <i class="fa-solid fa-calculator"></i>
                                </div>
                                <div>
                                    <p class="font-black text-gray-900">Matemáticas</p>
                                    <p class="text-xs text-gray-400">92/100 Dominio</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Subjects Grid -->
                <section>
                    <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center header-section">
                        <i class="fa-solid fa-book-open mr-3 text-orange-600"></i>
                        Tu Dominio por Materia
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div 
                            v-for="(item, index) in subject_mastery" 
                            :key="item.subject"
                            class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-200 transition-all group stat-card cursor-pointer"
                            @click="playSound('pop')"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <p class="font-black text-gray-900 group-hover:text-orange-600 transition-colors">{{ item.subject }}</p>
                                <span class="text-xs font-black text-gray-400">{{ Math.round(item.mastery_score * 10) }}%</span>
                            </div>
                            <div class="w-full bg-gray-50 h-2 rounded-full overflow-hidden mb-2">
                                <div 
                                    class="bg-orange-500 h-full transition-all duration-1000"
                                    :style="{ width: (item.mastery_score * 10) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-20">
                    <Link 
                        :href="route('simulator.index')" 
                        @click="playSound('success')"
                        class="flex items-center gap-6 p-6 bg-white rounded-3xl border-2 border-transparent hover:border-orange-500 transition-all shadow-sm group hover:scale-[1.02] active:scale-95"
                    >
                        <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-2xl group-hover:rotate-12 transition-transform">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <p class="font-black text-lg text-gray-900">Nuevo Simulacro</p>
                            <p class="text-sm text-gray-400 font-medium">Mide tu nivel con 120 preguntas reales.</p>
                        </div>
                    </Link>
                    <Link 
                        :href="route('quiz.index')" 
                        @click="playSound('success')"
                        class="flex items-center gap-6 p-6 bg-white rounded-3xl border-2 border-transparent hover:border-blue-500 transition-all shadow-sm group hover:scale-[1.02] active:scale-95"
                    >
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl group-hover:rotate-12 transition-transform">
                            <i class="fa-solid fa-bolt-lightning"></i>
                        </div>
                        <div>
                            <p class="font-black text-lg text-gray-900">Sesión de Práctica</p>
                            <p class="text-sm text-gray-400 font-medium">Enfócate en temas específicos para mejorar.</p>
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
</style>
