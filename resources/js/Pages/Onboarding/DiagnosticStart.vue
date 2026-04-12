<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    estimated_minutes: {
        type: Number,
        default: 30,
    },
    question_target: {
        type: Number,
        default: 36,
    },
});

const form = useForm({});

const startCalibration = () => {
    form.post(route('onboarding.diagnostic.start'));
};
</script>

<template>
    <Head title="Protocolo de Diagnóstico - NexusEdu" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black text-gray-900">Onboarding Cero</h2>
        </template>

        <div class="min-h-screen bg-[radial-gradient(circle_at_15%_0%,#1f2937_0%,#0b0f1a_45%,#020617_100%)] px-4 py-10 md:px-8">
            <div class="max-w-4xl mx-auto">
                <div class="rounded-3xl border border-cyan-400/30 bg-black/35 p-8 md:p-12 shadow-[0_0_45px_rgba(6,182,212,0.2)] text-white">
                    <p class="text-[11px] uppercase tracking-[0.28em] font-black text-cyan-300">Calibración inicial</p>
                    <h1 class="mt-3 text-3xl md:text-5xl font-black leading-tight">
                        Bienvenido, Operador. Antes de asignarte misiones, la IA necesita calibrar tu nivel base.
                    </h1>
                    <p class="mt-4 text-lg md:text-xl font-bold text-cyan-100">Inicia tu Protocolo de Diagnóstico.</p>

                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/15 bg-white/5 p-5">
                            <p class="text-xs uppercase tracking-widest font-black text-cyan-300">Duración aproximada</p>
                            <p class="mt-2 text-3xl font-black">{{ estimated_minutes }} min</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/5 p-5">
                            <p class="text-xs uppercase tracking-widest font-black text-cyan-300">Cobertura</p>
                            <p class="mt-2 text-3xl font-black">{{ question_target }} reactivos</p>
                            <p class="text-sm text-gray-300 mt-1">Cubre todas las áreas</p>
                        </div>
                    </div>

                    <div class="mt-9 rounded-2xl border border-cyan-400/30 bg-cyan-500/10 p-4">
                        <p class="text-sm font-semibold text-cyan-100">
                            Este diagnóstico desbloquea tu Dashboard táctico y permite que el motor adaptativo detecte debilidades reales desde el día 1.
                        </p>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="startCalibration"
                            class="rounded-xl bg-linear-to-r from-cyan-400 to-blue-500 text-black font-black uppercase tracking-wider px-7 py-3 shadow-[0_0_16px_rgba(34,211,238,0.45)] hover:brightness-110 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Iniciando protocolo...' : 'Iniciar Calibración' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
