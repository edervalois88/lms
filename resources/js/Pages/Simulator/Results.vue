<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { animate, spring, stagger } from 'motion';
import Card from '@/Components/UI/Card.vue';
import { playSound } from '@/Utils/SoundService';

const props = defineProps({
    exam: Object,
    correct: Number,
    total: Number,
    percentage: Number,
    message: String,
    goal: Object,
    ai_suggestions: {
        type: Array,
        default: () => [],
    },
    ai_opportunities: {
        type: Object,
        default: () => ({
            critical_areas: [],
            strengths: [],
            study_plan: null,
            motivational_message: null,
        }),
    },
    xp_awarded: {
        type: Number,
        default: 0,
    },
    subject_breakdown: {
        type: Array,
        default: () => [],
    },
    incorrect_answers_count: {
        type: Number,
        default: 0,
    },
});

const showHero = ref(false);
const confettiPieces = computed(() => Array.from({ length: 22 }, (_, i) => i));

const ringStyle = computed(() => ({
    background: `conic-gradient(#22c55e ${props.percentage}%, rgba(148,163,184,0.22) ${props.percentage}% 100%)`,
}));

const scoreCategory = computed(() => {
    if (props.percentage >= 80) return { color: 'text-emerald-300', glow: 'green' };
    if (props.percentage >= 60) return { color: 'text-cyan-300', glow: 'orange' };
    if (props.percentage >= 40) return { color: 'text-orange-300', glow: 'orange' };
    return { color: 'text-rose-300', glow: 'red' };
});

const tacticalLabel = (row) => row.status === 'mastered' ? 'Dominada' : 'Área de oportunidad';

const tacticalClass = (row) => row.status === 'mastered'
    ? 'border-emerald-400/50 bg-emerald-500/10 text-emerald-100'
    : 'border-orange-400/50 bg-orange-500/10 text-orange-100';

const hasAiOpportunityInsights = computed(() => {
    const critical = props.ai_opportunities?.critical_areas?.length || 0;
    const strengths = props.ai_opportunities?.strengths?.length || 0;
    const studyPlan = typeof props.ai_opportunities?.study_plan === 'string' && props.ai_opportunities.study_plan.length > 0;
    const motivation = typeof props.ai_opportunities?.motivational_message === 'string' && props.ai_opportunities.motivational_message.length > 0;

    return critical > 0 || strengths > 0 || studyPlan || motivation;
});

onMounted(() => {
    showHero.value = true;
    playSound(props.percentage >= 60 ? 'success' : 'pop');

    animate('.end-screen-fade', { opacity: [0, 1], y: [20, 0] }, { duration: 0.55, easing: spring() });
    animate('.metric-card', { opacity: [0, 1], y: [18, 0] }, { delay: stagger(0.08), duration: 0.45, easing: spring() });

    if (props.xp_awarded > 0) {
        animate('.xp-hero', { opacity: [0, 1], scale: [0.85, 1] }, { duration: 0.7, easing: spring({ stiffness: 160, damping: 16 }) });
    }
});
</script>

<template>
    <Head title="Resultados del Simulacro - NexusEdu" />

    <AuthenticatedLayout>

    <div class="app-shell min-h-screen text-white px-4 py-10 md:px-8 end-screen-fade">
        <div class="max-w-7xl mx-auto space-y-8 relative">
            <div v-if="xp_awarded > 0 && showHero" class="pointer-events-none absolute inset-0 overflow-hidden">
                <span
                    v-for="n in confettiPieces"
                    :key="n"
                    class="confetti-piece"
                    :style="{
                        left: `${(n * 13) % 100}%`,
                        animationDelay: `${(n % 8) * 0.12}s`,
                        background: n % 2 ? '#fb923c' : '#34d399',
                    }"
                />
            </div>

            <Card v-if="xp_awarded > 0" glow="orange" class="xp-hero relative overflow-hidden">
                <div class="absolute -top-24 -right-20 h-48 w-48 rounded-full bg-orange-500/25 blur-3xl" />
                <div class="absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-emerald-500/20 blur-3xl" />

                <div class="relative z-10 text-center">
                    <p class="text-xs uppercase tracking-[0.25em] font-black text-orange-300">Simulacro estricto completado</p>
                    <h1 class="text-3xl md:text-5xl font-black mt-2 text-white">¡Misión Completada! +{{ xp_awarded }} XP</h1>
                    <p class="mt-3 text-gray-300 font-semibold">{{ message }}</p>
                </div>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card :glow="scoreCategory.glow" class="metric-card lg:col-span-2">
                    <p class="text-xs uppercase tracking-[0.22em] font-black text-gray-300">Puntaje final</p>
                    <div class="mt-4 flex flex-col md:flex-row md:items-center gap-8">
                        <div class="relative w-44 h-44 mx-auto md:mx-0 rounded-full p-3" :style="ringStyle">
                            <div class="w-full h-full rounded-full bg-gray-950 border border-white/10 grid place-items-center">
                                <div class="text-center">
                                    <p class="text-4xl font-black" :class="scoreCategory.color">{{ percentage }}%</p>
                                    <p class="text-xs uppercase tracking-[0.2em] text-gray-400 font-black mt-1">Precisión</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-2xl md:text-3xl font-black text-white">{{ correct }} / {{ total }} <span class="text-gray-400 text-xl">({{ percentage }}%)</span></p>
                            <p class="text-sm text-gray-300">Errores detectados: <span class="font-black text-orange-300">{{ incorrect_answers_count }}</span></p>
                            <p v-if="goal" class="text-sm text-gray-400">
                                Meta UNAM/IPN: <span class="text-white font-bold">{{ goal.name }}</span>
                                · mínimo histórico: <span class="font-black">{{ goal.min_score }}</span>
                            </p>
                        </div>
                    </div>
                </Card>

                <Card glow="green" class="metric-card">
                    <p class="text-xs uppercase tracking-[0.22em] font-black text-emerald-300">Revisión táctica</p>
                    <h3 class="mt-3 text-2xl font-black text-white">Convierte tus errores en puntos</h3>
                    <p class="mt-2 text-sm text-gray-300">Analiza solo lo que fallaste y activa el Tutor IA para cerrar brechas de conocimiento.</p>

                    <Link
                        :href="route('simulator.review', exam.id)"
                        class="mt-6 inline-flex w-full items-center justify-center rounded-2xl min-h-11 bg-orange-500 py-4 text-sm font-black uppercase tracking-wider text-black shadow-[0_0_15px_rgba(255,165,0,0.5)] hover:bg-orange-400 transition-colors"
                    >
                        Entrar a Revisión Táctica
                    </Link>

                    <Link
                        :href="route('simulator.index')"
                        class="mt-3 inline-flex w-full items-center justify-center rounded-2xl min-h-11 border border-white/20 py-3 text-sm font-bold text-gray-200 hover:bg-white/5"
                    >
                        Nuevo simulacro
                    </Link>
                </Card>
            </div>

            <Card glow="orange" class="metric-card">
                <p class="text-xs uppercase tracking-[0.22em] font-black text-orange-300">Desglose táctico por materia</p>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="row in subject_breakdown"
                        :key="row.subject"
                        class="rounded-2xl border p-4"
                        :class="tacticalClass(row)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-black text-lg leading-tight">{{ row.subject }}</p>
                            <span class="text-[10px] uppercase tracking-widest font-black px-2 py-1 rounded-full border border-current/40">
                                {{ tacticalLabel(row) }}
                            </span>
                        </div>

                        <p class="mt-3 text-sm font-bold">{{ row.correct }} / {{ row.total }} · {{ row.accuracy }}%</p>

                        <div class="mt-3 h-2 rounded-full bg-black/20 overflow-hidden">
                            <div class="h-full rounded-full" :style="{ width: `${row.accuracy}%`, background: row.status === 'mastered' ? '#34d399' : '#fb923c' }" />
                        </div>
                    </div>
                </div>
            </Card>

            <Card v-if="hasAiOpportunityInsights" glow="orange" class="metric-card">
                <p class="text-xs uppercase tracking-[0.22em] font-black text-orange-300">Diagnóstico IA de áreas de oportunidad</p>

                <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-orange-400/30 bg-orange-500/10 p-4">
                        <p class="text-sm font-black uppercase tracking-wider text-orange-200">Prioridad de refuerzo</p>
                        <ul class="mt-3 space-y-2 text-sm text-gray-200">
                            <li v-for="area in ai_opportunities.critical_areas" :key="area">• {{ area }}</li>
                            <li v-if="!ai_opportunities.critical_areas?.length" class="text-gray-400">Sin áreas críticas detectadas.</li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-emerald-400/30 bg-emerald-500/10 p-4">
                        <p class="text-sm font-black uppercase tracking-wider text-emerald-200">Fortalezas actuales</p>
                        <ul class="mt-3 space-y-2 text-sm text-gray-200">
                            <li v-for="strength in ai_opportunities.strengths" :key="strength">• {{ strength }}</li>
                            <li v-if="!ai_opportunities.strengths?.length" class="text-gray-400">Aún no hay fortalezas consistentes.</li>
                        </ul>
                    </div>
                </div>

                <div v-if="ai_opportunities.study_plan" class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] font-black text-cyan-300">Plan sugerido</p>
                    <p class="mt-2 text-sm text-gray-200">{{ ai_opportunities.study_plan }}</p>
                </div>

                <p v-if="ai_opportunities.motivational_message" class="mt-4 text-sm font-semibold text-gray-200">
                    {{ ai_opportunities.motivational_message }}
                </p>
            </Card>

            <Card v-if="ai_suggestions.length > 0" glow="red" class="metric-card">
                <p class="text-xs uppercase tracking-[0.22em] font-black text-rose-300">Sugerencias estratégicas IA</p>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="suggest in ai_suggestions" :key="suggest.name" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-lg font-black text-white">{{ suggest.name }}</p>
                        <p class="mt-2 text-sm text-gray-300">{{ suggest.reason }}</p>
                    </div>
                </div>
            </Card>
        </div>
    </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.confetti-piece {
    position: absolute;
    top: -20px;
    width: 8px;
    height: 18px;
    border-radius: 999px;
    opacity: 0;
    animation: fall 2.4s linear forwards;
}

@keyframes fall {
    0% {
        opacity: 0;
        transform: translateY(0) rotate(0deg);
    }
    12% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translateY(90vh) rotate(540deg);
    }
}
</style>

