<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <Head title="Checkout cancelado" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[radial-gradient(circle_at_20%_0%,#1f2937_0%,#0b0f1a_45%,#020617_100%)] px-4 py-10 md:px-8 text-white">
            <div class="max-w-3xl mx-auto rounded-3xl border border-slate-400/30 bg-slate-900/70 p-8 md:p-12 shadow-[0_0_36px_rgba(148,163,184,0.25)]">
                <p class="text-[11px] uppercase tracking-[0.24em] font-black text-slate-300">Checkout</p>
                <h1 class="mt-3 text-3xl md:text-4xl font-black leading-tight">Transaccion cancelada</h1>
                <p class="mt-4 text-lg text-slate-200 font-semibold">
                    Tus datos estan seguros. Vuelve cuando estes listo para desbloquear el Nivel Elite.
                </p>

                <Link
                    :href="route('dashboard')"
                    class="mt-8 inline-flex rounded-xl border border-white/20 bg-white/5 px-6 py-3 text-white font-black uppercase tracking-wider hover:bg-white/10"
                >
                    Volver al Dashboard
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
