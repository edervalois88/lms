<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <Head title="Elite Desbloqueado" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[radial-gradient(circle_at_20%_0%,#1f2937_0%,#0b0f1a_45%,#020617_100%)] px-4 py-10 md:px-8 text-white">
            <div class="max-w-3xl mx-auto rounded-3xl border border-emerald-400/35 bg-emerald-500/10 p-8 md:p-12 shadow-[0_0_42px_rgba(16,185,129,0.3)] relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none">
                    <span v-for="i in 20" :key="i" class="confetti" :style="{ left: `${(i * 11) % 100}%`, animationDelay: `${(i % 8) * 0.12}s` }" />
                </div>

                <p class="text-[11px] uppercase tracking-[0.24em] font-black text-emerald-300">Pago confirmado</p>
                <h1 class="mt-3 text-4xl md:text-5xl font-black leading-tight">Protocolo Elite Desbloqueado</h1>
                <p class="mt-4 text-lg text-emerald-100 font-semibold">
                    Tu acceso sin restricciones ya esta activo. Es hora de dominar el examen con poder total.
                </p>

                <Link
                    :href="route('dashboard')"
                    class="mt-8 inline-flex rounded-xl bg-linear-to-r from-emerald-400 to-cyan-400 px-6 py-3 text-black font-black uppercase tracking-wider shadow-[0_0_18px_rgba(16,185,129,0.45)]"
                >
                    Ir al Dashboard
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.confetti {
    position: absolute;
    top: -16px;
    width: 8px;
    height: 16px;
    border-radius: 999px;
    background: linear-gradient(to bottom, #34d399, #22d3ee);
    opacity: 0;
    animation: fall 2.2s linear forwards;
}

@keyframes fall {
    0% { opacity: 0; transform: translateY(0) rotate(0deg); }
    12% { opacity: 1; }
    100% { opacity: 0; transform: translateY(75vh) rotate(540deg); }
}
</style>
