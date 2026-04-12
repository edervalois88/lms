<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    major: Object,
    user_gpa: [Number, String, null],
    stats: Object,
    recent_exams: Array,
    subject_mastery: Array,
});

const mission = computed(() => {
    const projected = Number(props.stats?.projection?.projected_score ?? 0);
    const gap = Number(props.stats?.projection?.gap_to_goal ?? 0);
    const target = projected + Math.max(0, gap);
    const pct = target > 0 ? Math.min(100, Math.round((projected / target) * 100)) : 0;

    return {
        university: props.major?.campus?.university?.acronym ?? 'SIN OBJETIVO',
        campus: props.major?.campus?.name ?? 'Define campus en perfil',
        career: props.major?.name ?? 'Define tu carrera objetivo',
        projected,
        target,
        gap,
        progress: pct,
    };
});

const pathNodes = computed(() => {
    const levels = (props.subject_mastery ?? []).map((item) => {
        const score = Math.round(Number(item.mastery_score ?? 0) * 10);

        let status = 'pending';
        if (score >= 80) status = 'done';
        else if (score >= 45) status = 'active';

        return {
            subject: item.subject,
            score,
            status,
            topics: item.topics_count ?? 5,
        };
    });

    if (levels.length === 0) {
        return [
            { subject: 'Matemáticas', score: 0, status: 'active', topics: 5 },
            { subject: 'Física', score: 0, status: 'pending', topics: 5 },
            { subject: 'Química', score: 0, status: 'pending', topics: 5 },
            { subject: 'Español', score: 0, status: 'pending', topics: 5 },
        ];
    }

    return levels;
});

const activeNode = computed(() => {
    return pathNodes.value.find((node) => node.status === 'active') ?? pathNodes.value[0];
});

const rank = computed(() => props?.stats?.streak ? (props.stats.streak >= 30 ? 'ÉLITE' : props.stats.streak >= 10 ? 'AVANZADO' : 'EN PROGRESO') : 'INICIANDO');

const lessonTitle = computed(() => `Completa el reto de ${activeNode.value?.subject ?? 'hoy'}`);
const lessonPrompt = computed(() => `${activeNode.value?.subject ?? 'Tema'} y práctica, por favor.`);
</script>

<template>
    <Head title="Dashboard - NexusEdu" />

    <div class="min-h-screen bg-midnight text-white">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-8 md:py-10 grid grid-cols-1 lg:grid-cols-12 gap-8">

            <section class="lg:col-span-8 space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/3 p-5 md:p-7">
                    <div class="flex items-center gap-4">
                        <button type="button" class="w-9 h-9 rounded-full border border-white/15 text-gray-300 hover:text-white hover:border-white/30">✕</button>
                        <div class="flex-1 h-3 rounded-full bg-white/10 border border-white/10 overflow-hidden">
                            <div class="h-full bg-linear-to-r from-lime-400 to-emerald-500" :style="{ width: mission.progress + '%' }"></div>
                        </div>
                        <span class="text-xs font-black text-gray-300">{{ mission.progress }}%</span>
                    </div>

                    <div class="mt-8 mx-auto max-w-2xl bg-[#e5e5e5] text-[#3e3e3e] rounded-3xl border border-[#d4d4d4] p-6 md:p-8 shadow-inner">
                        <h2 class="text-3xl md:text-4xl font-black text-center tracking-tight">{{ lessonTitle }}</h2>

                        <div class="mt-6 flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-amber-700 text-white flex items-center justify-center text-2xl font-black">N</div>
                            <div class="rounded-2xl border border-[#cfcfcf] bg-[#efefef] px-4 py-2 text-lg">
                                {{ lessonPrompt }}
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-[#cfcfcf] bg-[#ececec] p-4">
                            <p class="text-lg text-[#4b4b4b]">{{ activeNode?.subject }} y <span class="border-b-4 border-sky-400 px-6"></span>.</p>
                            <p class="mt-6 text-sm text-[#686868]">Objetivo: subir dominio de {{ activeNode?.score ?? 0 }}% a {{ Math.min(100, (activeNode?.score ?? 0) + 10) }}%.</p>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-between gap-4">
                        <Link :href="route('practice.daily')" class="rounded-2xl border border-white/20 px-6 py-3 text-sm font-black uppercase text-gray-300 hover:text-white hover:border-white/40">Saltar</Link>
                        <Link :href="route('quiz.index')" class="rounded-2xl bg-orange-600 hover:bg-orange-500 px-8 py-3 text-sm font-black uppercase">Comprobar</Link>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/3 p-5 md:p-7">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-[11px] font-black uppercase tracking-wider text-blue-400">Ruta de entrenamiento</p>
                        <p class="text-sm text-gray-400">{{ mission.university }} • {{ mission.campus }}</p>
                    </div>

                    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="rounded-2xl bg-white/3 border border-white/10 p-4">
                            <p class="text-[10px] uppercase tracking-wider text-gray-400 font-black">Aciertos proyectados</p>
                            <p class="text-2xl font-black text-white mt-1">{{ mission.projected }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/3 border border-white/10 p-4">
                            <p class="text-[10px] uppercase tracking-wider text-gray-400 font-black">Meta de ingreso</p>
                            <p class="text-2xl font-black text-orange-400 mt-1">{{ mission.target }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/3 border border-white/10 p-4">
                            <p class="text-[10px] uppercase tracking-wider text-gray-400 font-black">Brecha</p>
                            <p class="text-2xl font-black mt-1" :class="mission.gap <= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ mission.gap }}</p>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="(node, idx) in pathNodes.slice(0, 4)" :key="node.subject + idx" class="rounded-xl border border-white/10 bg-white/2 p-3">
                            <div class="flex items-center justify-between">
                                <p class="font-black text-white">{{ node.subject }}</p>
                                <span class="text-xs font-black px-2 py-1 rounded-full"
                                    :class="node.status === 'done' ? 'bg-emerald-500/20 text-emerald-300' : node.status === 'active' ? 'bg-orange-500/20 text-orange-300' : 'bg-gray-500/20 text-gray-300'">
                                    {{ node.score }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="lg:col-span-4 space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/3 p-5">
                    <p class="text-[11px] font-black uppercase tracking-wider text-gray-400">Estado del operador</p>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-white/4 p-3 border border-white/10">
                            <p class="text-[10px] text-gray-400 uppercase font-black">Racha</p>
                            <p class="text-xl font-black text-orange-400 mt-1">{{ stats.streak }}</p>
                        </div>
                        <div class="rounded-xl bg-white/4 p-3 border border-white/10">
                            <p class="text-[10px] text-gray-400 uppercase font-black">Precisión</p>
                            <p class="text-xl font-black text-blue-300 mt-1">{{ stats.accuracy }}%</p>
                        </div>
                        <div class="rounded-xl bg-white/4 p-3 border border-white/10">
                            <p class="text-[10px] text-gray-400 uppercase font-black">Rango</p>
                            <p class="text-xl font-black text-emerald-300 mt-1">{{ rank }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/3 p-5">
                    <p class="text-[11px] font-black uppercase tracking-wider text-orange-400">Acciones rápidas</p>
                    <div class="mt-4 space-y-3">
                        <Link :href="route('simulator.index')" class="block rounded-xl bg-orange-600 hover:bg-orange-500 px-4 py-3 font-black text-sm text-center">Iniciar simulacro</Link>
                        <Link :href="route('practice.daily')" class="block rounded-xl bg-emerald-600/80 hover:bg-emerald-500 px-4 py-3 font-black text-sm text-center">Completar Daily XP</Link>
                        <Link :href="route('profile.edit')" class="block rounded-xl bg-white/10 hover:bg-white/20 border border-white/10 px-4 py-3 font-black text-sm text-center">Ajustar objetivo</Link>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/3 p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-black uppercase tracking-wider text-gray-400">Últimos simulacros</p>
                        <span class="text-xs text-gray-500">{{ recent_exams?.length || 0 }}</span>
                    </div>

                    <div class="mt-4 space-y-3" v-if="recent_exams?.length">
                        <div v-for="exam in recent_exams" :key="exam.id" class="rounded-xl border border-white/10 bg-white/2 p-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-black text-white">Simulacro #{{ exam.id }}</p>
                                <p class="text-sm font-black text-orange-300">{{ exam.score ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <p v-else class="mt-4 text-sm text-gray-400">Aún no tienes simulacros registrados.</p>
                </div>
            </aside>
        </div>
    </div>
</template>
