<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { animate, spring, stagger } from 'motion';
import { playSound } from '@/Utils/SoundService';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
    major: Object,
    user_gpa: [Number, String, null],
    stats: Object,
    recent_exams: Array,
    subject_mastery: Array,
    bootcamp_recommendation: Object,
});

const page = usePage();
const { theme, initializeTheme, toggleTheme } = useTheme();

const isAdmin = computed(() => Boolean(page.props?.auth?.is_admin));
const equippedCosmetics = computed(() => page.props?.auth?.cosmetics?.equipped || {});
const themePalette = computed(() => {
    const metadata = equippedCosmetics.value?.ui_theme?.metadata || {};

    return {
        primary: metadata.primary_color || '#ff6b00',
        secondary: metadata.secondary_color || '#f97316',
        soft: metadata.soft_color || 'rgba(255, 107, 0, 0.18)',
    };
});
const avatarIcon = computed(() => equippedCosmetics.value?.avatar?.metadata?.icon_class || 'fa-solid fa-user');
const profileTitle = computed(() => equippedCosmetics.value?.profile_title?.metadata?.label || null);

// Access global shared gamification state
const gamification = computed(() => props.auth?.gamification || { current: 1, xp: 0, progress: 0, rank: 'Novato' });

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
    if (gap === null) return { text: 'NODO INACTIVO', color: 'text-gray-500', bg: 'bg-white/5' };
    if (gap <= 0) return { text: 'ZONA DE INGRESO', color: 'text-green-400', bg: 'bg-green-400/10' };
    if (gap <= 10) return { text: 'META PRÓXIMA', color: 'text-orange-400', bg: 'bg-orange-400/10' };
    return { text: 'BRECHA CRÍTICA', color: 'text-red-400', bg: 'bg-red-400/10' };
});

const bootcampSubjects = computed(() => {
    const subjects = props.bootcamp_recommendation?.subjects || [];
    return subjects.map((item) => item.name).filter(Boolean).slice(0, 2);
});

const bootcampProtocolText = computed(() => {
    if (bootcampSubjects.value.length >= 2) {
        return `Protocolo de refuerzo necesario en: ${bootcampSubjects.value[0]} y ${bootcampSubjects.value[1]}.`;
    }

    if (bootcampSubjects.value.length === 1) {
        return `Protocolo de refuerzo necesario en: ${bootcampSubjects.value[0]}.`;
    }

    return 'Protocolo de refuerzo en generación. Ejecuta misión táctica para calibrar tu perfil.';
});

const tacticalModule = computed(() => {
    if (bootcampSubjects.value.length > 0) {
        return bootcampSubjects.value.join(' y ');
    }

    return 'materias clave';
});

const estimatedSessions = computed(() => {
    const gap = Number(props.stats?.projection?.gap_to_goal ?? 0);
    if (!Number.isFinite(gap) || gap <= 0) {
        return 1;
    }

    return Math.max(1, Math.ceil(gap / 8));
});

const tacticalMessage = computed(() => {
    const gap = Number(props.stats?.projection?.gap_to_goal ?? 0);

    if (!Number.isFinite(gap) || gap <= 0) {
        return 'Protocolo detectado: ya estás en zona de ingreso. Mantén sesiones de consolidación para sostener tu ventaja.';
    }

    return `Protocolo detectado: Para neutralizar los ${gap} aciertos restantes, debemos priorizar el módulo de ${tacticalModule.value}. El algoritmo estima éxito en ${estimatedSessions.value} sesiones.`;
});

const accuracyDelta = computed(() => Number(props.stats?.accuracy_delta ?? 0));
const efficiencyDirection = computed(() => {
    if (accuracyDelta.value > 0) return 'up';
    if (accuracyDelta.value < 0) return 'down';
    return 'stable';
});
const efficiencyLabel = computed(() => {
    if (efficiencyDirection.value === 'up') {
        return `+${Math.abs(accuracyDelta.value).toFixed(1)}% eficiencia`;
    }

    if (efficiencyDirection.value === 'down') {
        return `-${Math.abs(accuracyDelta.value).toFixed(1)}% eficiencia`;
    }

    return 'Sin variación semanal';
});
const floatingMessage = computed(() => {
    if ((props.stats?.streak || 0) > 0) {
        return `Llevas ${props.stats.streak} día(s) de racha activa. Mantén el ritmo.`;
    }

    return 'Inicia práctica diaria para construir tu primera racha.';
});

onMounted(() => {
    initializeTheme();

    animate(".hud-element", { opacity: [0, 1], y: [20, 0] }, { 
        delay: stagger(0.1),
        duration: 0.8,
        easing: spring() 
    });

    animate(".progress-orb", { scale: [0, 1] }, { duration: 1, easing: spring() });
});
</script>

<template>
    <Head title="Command Center - NexusEdu" />

    <div class="app-shell min-h-screen bg-midnight text-white font-sans selection:bg-orange-500/30">
        
        <!-- Top Immersive Status Bar -->
        <div class="app-nav bg-cyber-gray/80 backdrop-blur-md border-b border-white/5 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl font-black shadow-orange-glow text-white" :style="{ background: `linear-gradient(135deg, ${themePalette.primary}, ${themePalette.secondary})` }">
                        <i :class="avatarIcon"></i>
                    </div>
                    <div class="hidden md:block">
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em]">Status: Operador | GPA: {{ props.user_gpa || '---' }}</p>
                        <p class="text-sm font-black">{{ $page.props.auth.user.name }}</p>
                    </div>
                </div>

                <!-- Global XP Bar -->
                <div class="grow max-w-md mx-10 hidden lg:block">
                    <div class="flex justify-between items-end mb-1 px-1">
                        <span class="text-[10px] font-black uppercase tracking-widest" :style="{ color: themePalette.primary }">{{ profileTitle || $page.props.auth.gamification?.rank || 'Novato' }}</span>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">NIVEL {{ $page.props.auth.gamification?.current || 1 }}</span>
                    </div>
                    <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden border border-white/5">
                        <div 
                            class="h-full shadow-orange-glow transition-all duration-1000"
                            :style="{ width: ($page.props.auth.gamification?.progress || 0) + '%', backgroundColor: themePalette.primary }"
                        ></div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <button
                        type="button"
                        @click="toggleTheme()"
                        class="nx-icon-btn"
                        :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                    >
                        <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                    </button>

                    <Link
                        v-if="isAdmin"
                        :href="route('admin.index')"
                        class="nx-icon-btn"
                        title="Panel admin"
                    >
                        <i class="fa-solid fa-shield-halved"></i>
                    </Link>

                    <Link
                        :href="route('profile.edit')"
                        class="nx-icon-btn"
                        title="Perfil"
                    >
                        <i class="fa-solid fa-user-gear"></i>
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-10 h-10 bg-red-500/10 rounded-full flex items-center justify-center text-red-400 hover:bg-red-500 hover:text-white transition-colors border border-red-500/20"
                        title="Cerrar sesión"
                    >
                        <i class="fa-solid fa-power-off"></i>
                    </Link>

                    <div class="flex items-center gap-2 px-4 py-2 bg-orange-500/10 rounded-xl border border-orange-500/20 group cursor-help">
                        <i class="fa-solid fa-fire text-orange-500 group-hover:animate-bounce"></i>
                        <span class="font-black text-sm">{{ stats.streak }} DÍAS</span>
                    </div>
                    <Link
                        :href="route('review.index')"
                        class="nx-icon-btn"
                        title="Repetición espaciada"
                    >
                        <i class="fa-solid fa-bell"></i>
                    </Link>
                </div>
            </div>
        </div>

        <div class="py-12 px-6">
            <div class="max-w-7xl mx-auto space-y-12">
                
                <!-- Main HUD Wrap -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <!-- Primary Mission HUD -->
                    <div class="lg:col-span-8 space-y-10">
                        <div class="glass-morphism-dark p-10 rounded-[3rem] border border-white/5 relative overflow-hidden hud-element shadow-2xl">
                            <!-- Background HUD Lines -->
                            <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
                                <div class="grid grid-cols-6 h-full border-x border-white"></div>
                            </div>

                            <div class="relative z-10 space-y-12">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    <div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse shadow-blue-glow"></span>
                                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-[0.4em]">Misión de Ingreso Activa: {{ major?.campus?.university?.acronym }}</p>
                                        </div>
                                        <h2 class="text-5xl font-black uppercase italic tracking-tighter">{{ authMajor?.name || 'Sector no Definido' }}</h2>
                                        <p class="text-gray-500 font-bold uppercase tracking-widest text-xs mt-2">{{ major?.campus?.name || 'Protocolo de Onboarding Requerido' }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <div :class="['px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] border shadow-2xl', gapStatus.bg, gapStatus.color, gapStatus.color.replace('text', 'border').replace('400', '400/20')]">
                                            {{ gapStatus.text }}
                                        </div>
                                        <div v-if="major" class="px-3 py-1 bg-red-500/10 border border-red-500/20 text-red-500 font-black text-[9px] rounded uppercase tracking-widest">
                                            AMENAZA: {{ major.difficulty_category }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                                    <div class="space-y-6">
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <p class="text-6xl font-black text-white glow-text">{{ stats.projection.projected_score }}</p>
                                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Aciertos Proyectados</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-2xl font-black text-orange-500">{{ authMajor?.min_score || '---' }}</p>
                                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Meta Nivel</p>
                                            </div>
                                        </div>
                                        <!-- Big Progress HUD -->
                                        <div class="relative pt-4">
                                            <div class="w-full bg-white/5 h-4 rounded-2xl overflow-hidden p-1 border border-white/5">
                                                <div 
                                                    class="h-full bg-linear-to-r from-orange-600 to-red-500 rounded-xl transition-all duration-[2s] shadow-orange-glow progress-fill"
                                                    :style="{ width: progressPercentage + '%' }"
                                                ></div>
                                            </div>
                                            <div class="flex justify-between mt-4 text-[9px] font-black text-gray-600 uppercase tracking-[0.3em]">
                                                <span>Frontera Inicial</span>
                                                <span class="text-orange-500">{{ progressPercentage }}% Eficacia</span>
                                                <span>Victoria</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AI Strategy Box -->
                                    <div class="bg-white/5 border border-white/5 rounded-[2.5rem] p-8 relative group hover:bg-white/8 transition-all cursor-pointer">
                                        <div class="absolute -top-4 -left-4 w-12 h-12 bg-orange-600 rounded-2xl flex items-center justify-center text-white shadow-orange-glow transform -rotate-12">
                                            <i class="fa-solid fa-robot"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-300 italic leading-relaxed pl-4">
                                            {{ tacticalMessage }}
                                        </p>
                                    </div>
                                </div>

                                <div class="rounded-[2.5rem] border border-red-500/35 bg-linear-to-br from-red-500/12 via-orange-500/8 to-transparent p-8 shadow-[0_0_35px_rgba(249,115,22,0.22)]">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-red-300">Bootcamp Táctico Recomendado</p>
                                            <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tight mt-2">Misión de Rescate Adaptativa</h3>
                                            <p class="text-sm font-bold text-orange-200/90 mt-3">{{ bootcampProtocolText }}</p>
                                            <p v-if="props.bootcamp_recommendation?.is_fallback" class="text-[10px] font-black uppercase tracking-widest text-orange-300/80 mt-3">
                                                Modo calibración activo: seleccionadas materias estratégicas por fallback.
                                            </p>
                                        </div>

                                        <Link
                                            :href="route('quiz.bootcamp.start')"
                                            method="post"
                                            as="button"
                                            class="px-8 py-4 rounded-2xl bg-linear-to-r from-orange-500 to-red-500 text-white font-black uppercase tracking-wider shadow-[0_0_20px_rgba(249,115,22,0.45)] hover:scale-[1.02] transition-transform"
                                            @click="playSound('success')"
                                        >
                                            Iniciar Misión Táctica (10 Reactivos)
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Modules -->
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 hud-element">
                            <Link
                                :href="route('simulator.index')"
                                class="group relative overflow-hidden rounded-[2.25rem] border border-white/10 bg-[#0b1220] p-1.5 transition-all duration-300 hover:-translate-y-1 hover:border-orange-400/40 hover:shadow-[0_14px_35px_rgba(249,115,22,0.22)]"
                                @click="playSound('success')"
                            >
                                <div class="absolute inset-0 opacity-70 bg-[radial-gradient(circle_at_0%_0%,rgba(249,115,22,0.25),transparent_60%)]"></div>
                                <div class="relative flex items-center gap-5 rounded-4xl border border-white/10 bg-[#060b14] px-6 py-7">
                                    <div class="w-16 h-16 shrink-0 rounded-2xl bg-orange-500/10 border border-orange-400/20 flex items-center justify-center text-3xl text-orange-400 transition-transform duration-300 group-hover:rotate-6">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-orange-300/90">Módulo Principal</p>
                                        <h3 class="text-3xl font-black uppercase italic tracking-tight leading-none mt-1">Simulacro</h3>
                                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.2em] mt-2">Ejecutar 120 reactivos</p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                :href="route('quiz.index')"
                                class="group relative overflow-hidden rounded-[2.25rem] border border-white/10 bg-linear-to-r from-orange-500 to-red-500 p-1.5 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_38px_rgba(255,93,43,0.35)]"
                                @click="playSound('success')"
                            >
                                <div class="relative flex items-center gap-5 rounded-4xl border border-black/20 bg-[#060b14] px-6 py-7">
                                    <div class="w-16 h-16 shrink-0 rounded-2xl bg-blue-500/10 border border-blue-400/25 flex items-center justify-center text-3xl text-blue-400 transition-transform duration-300 group-hover:rotate-6">
                                        <i class="fa-solid fa-bolt-lightning"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-orange-200/90">Módulo Activo</p>
                                        <h3 class="text-3xl font-black uppercase italic tracking-tight leading-none mt-1">Práctica</h3>
                                        <p class="text-[11px] font-bold text-gray-300 uppercase tracking-[0.2em] mt-2">Entrenamiento focalizado</p>
                                    </div>
                                </div>
                            </Link>

                            <Link
                                :href="route('practice.daily')"
                                class="group relative overflow-hidden rounded-[2.25rem] border border-white/10 bg-[#0b1220] p-1.5 transition-all duration-300 hover:-translate-y-1 hover:border-emerald-400/40 hover:shadow-[0_14px_35px_rgba(16,185,129,0.2)] md:col-span-2 xl:col-span-1"
                                @click="playSound('success')"
                            >
                                <div class="absolute inset-0 opacity-70 bg-[radial-gradient(circle_at_100%_0%,rgba(16,185,129,0.2),transparent_58%)]"></div>
                                <div class="relative flex items-center gap-5 rounded-4xl border border-white/10 bg-[#060b14] px-6 py-7">
                                    <div class="w-16 h-16 shrink-0 rounded-2xl bg-emerald-500/10 border border-emerald-400/20 flex items-center justify-center text-3xl text-emerald-400 transition-transform duration-300 group-hover:rotate-6">
                                        <i class="fa-solid fa-fire"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-emerald-300/90">Constancia</p>
                                        <h3 class="text-3xl font-black uppercase italic tracking-tight leading-none mt-1">Daily XP</h3>
                                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.2em] mt-2">Racha + recompensas</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Side HUD: Rankings & Mastery -->
                    <div class="lg:col-span-4 space-y-10 hud-element">
                        <!-- Stats Mini HUD -->
                        <div class="nx-panel p-7 rounded-[2.2rem] space-y-6">
                            <div class="flex items-center justify-between pb-4 border-b border-white/10">
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.22em]">Puntaje Global</h3>
                                <span class="w-9 h-9 rounded-xl bg-orange-500/15 border border-orange-400/25 text-orange-300 flex items-center justify-center">
                                    <i class="fa-solid fa-chart-simple"></i>
                                </span>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-white/3 px-5 py-6">
                                <div class="text-center">
                                    <p class="text-5xl font-black text-white glow-text leading-none">{{ stats.accuracy }}%</p>
                                    <p class="text-[10px] mt-2 font-black text-gray-500 uppercase tracking-[0.2em]">Precisión semanal</p>
                                    <p
                                        class="text-[10px] font-black uppercase mt-4 tracking-[0.16em]"
                                        :class="efficiencyDirection === 'up' ? 'text-green-400' : efficiencyDirection === 'down' ? 'text-red-400' : 'text-gray-400'"
                                    >
                                        <i :class="efficiencyDirection === 'up' ? 'fa-solid fa-arrow-up' : efficiencyDirection === 'down' ? 'fa-solid fa-arrow-down' : 'fa-solid fa-minus'"></i>
                                        {{ efficiencyLabel }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-center">
                                <div class="rounded-2xl border border-white/10 bg-white/3 py-3 px-2">
                                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.16em]">Exámenes</p>
                                    <p class="mt-1 text-lg font-black text-white">{{ stats.total_exams }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/3 py-3 px-2">
                                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.16em]">Racha</p>
                                    <p class="mt-1 text-lg font-black text-orange-300">{{ stats.streak }} días</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mastery Levels -->
                        <div class="nx-panel p-7 rounded-[2.2rem]">
                            <h3 class="text-[10px] font-black text-orange-300 uppercase tracking-[0.22em] mb-6">Zonas de Dominio</h3>
                            <div class="space-y-3">
                                <div v-for="item in subject_mastery.slice(0, 5)" :key="item.subject" class="rounded-2xl border border-white/10 bg-white/3 p-4 group cursor-pointer transition-colors hover:bg-white/5">
                                    <div class="flex justify-between items-end">
                                        <span class="text-xs font-black text-gray-300 group-hover:text-white transition-colors uppercase italic">{{ item.subject }}</span>
                                        <span class="text-[11px] font-black text-orange-300">{{ Math.round(item.mastery_score * 10) }}%</span>
                                    </div>
                                    <div class="h-2 mt-2.5 bg-black/25 rounded-full overflow-hidden border border-white/10">
                                        <div class="bg-linear-to-r from-orange-500 to-red-500 h-full group-hover:shadow-orange-glow transition-all duration-1000" :style="{ width: (item.mastery_score * 10) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Profile Info -->
                        <div class="nx-panel p-7 rounded-[2.2rem] relative overflow-hidden">
                           <div class="absolute -right-14 -bottom-14 w-44 h-44 bg-orange-500/10 rounded-full blur-3xl"></div>
                           <div class="relative z-10 flex items-center gap-5">
                               <div class="w-16 h-16 rounded-2xl border border-orange-400/30 bg-orange-500/10 text-orange-300 flex items-center justify-center text-3xl font-black">
                                   {{ $page.props.auth.gamification?.current || 1 }}
                               </div>
                               <div>
                                   <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Rango Actual</p>
                                   <p class="text-2xl font-black italic uppercase tracking-tighter text-white mt-1">{{ $page.props.auth.gamification?.rank || 'Novato' }}</p>
                               </div>
                           </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Notification Feed (Floating) -->
        <div class="fixed bottom-10 right-10 z-40 space-y-4 max-w-xs hidden xl:block">
            <div class="nx-panel p-5 rounded-2xl animate-float shadow-2xl flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-400/20 flex items-center justify-center text-emerald-300">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.16em] mb-1">Logro Desbloqueado</p>
                    <p class="text-xs font-bold text-gray-200">{{ floatingMessage }}</p>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.italic-glow {
    text-shadow: 0 0 30px rgba(255, 107, 0, 0.2);
}

.shadow-orange-glow {
    box-shadow: 0 0 40px rgba(255, 107, 0, 0.3);
}

.shadow-blue-glow {
    box-shadow: 0 0 40px rgba(0, 209, 255, 0.2);
}

@keyframes animate-pulse-glow {
    0%, 100% { opacity: 1; filter: brightness(1); }
    50% { opacity: 0.8; filter: brightness(1.5); }
}

.glow-text {
    text-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
}
</style>

